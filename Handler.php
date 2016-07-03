<?php namespace App\Exceptions;

use Exception;
use App\Exceptions\Exception;
use Whoops;
use Log;
use App\Services\Mail;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Http\Exception\HttpResponseException;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;




class Handler extends ExceptionHandler
{
    protected $redirect;
    protected $message;
    protected $mail;
    protected $log;

    public function __construct(Mail $mail,Log $log)
    {
        //  send to bagsnag sentry
        $this->mail = $mail;
        $this->log = $log;
    }

    protected $dontReport = [
        TokenMismatchException::class,
        HttpException::class,
        ModelNotFoundException::class,
        AuthorizationException::class,
        ValidationException::class];

    public function report(Exception $e)
        {
           parent::report($e);
        }

    public function render($request, Exception $e)
        {    
          // dd($e);
            $this->status = $this->getStatusCode($e);
            $this->message = $this->getMessage($e);
            $this->redirect = $request->fullUrl();

            $this->mail->sendReport($message);
            $this->log->error($e);

          switch($e)
          {
            case ($e instanceof Exception):
                 redirect()->back()->withErrors(['message' => $this->message], $status]);
                 break
            case ($this->isHttpException($e)):
                if (config('app.debug', false) || app()->environment() !== 'testing' && view()->exists("errors.{$status}")) {
                     response()->view("errors.{$status}", ['message' => $this->message], $this->status);
                    }
                if ($request->wantsJson()) {
                     response()->json(['message' => $this->message], $this->status);
                    }
                else{  
                     $this->renderExceptionWithWhoops($request,$e)
                }
                 break;
            default:
                return (new SymfonyDisplayer(config('app.debug')))->createResponse($e);
            }
            flash('alert', $this->message);
            return parent::render($request, $e);
         }

     protected function getStatusCode(\Exception $e)
      {
          if ($e instanceof HttpException) {
              return $e->getStatusCode();
          }
          if ($e instanceof isUnauthorizedException ) {
              return 403;
          }      
          if ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
              return 404;
          }              
          if ($e instanceof MethodNotAllowedHttpException) {
              return 405;
          }
          if ($e->getStatusCode() >= '500') {
              $this->mail->send($request, $e);
              return 500;
          }
      }

     protected function getMessage(\Exception $e)
     {
        switch($e)
        {
            case ($e instanceof DatabaseException):
                $message =  $e->getMessage();
                break
            case ($e instanceof ModelNotFoundException):
                $message =  trans('errors.model_not_found');
                break
            case($e instanceof Exception) 
                 $message =  trans('errors.general_exception');
                 break
            case ($e instanceof ModelNotFoundException):
                $message =  trans('errors.model_not_found');
                break
            case ($this->isUnauthorizedException($e)):
                $message =  trans('errors.model_not_found');
                break
            case ($e instanceof TokenMismatchException):
              $message =  trans('errors.invalid_token');
               break
            case ($e instanceof HttpResponseException):
              $message =  trans('errors.invalid_url');
                break
            case ($e instanceof PolicyException):
                $message =  trans('errors.action_for_reason');
                break
            case ($e instanceof BanException):
                $message =  trans('errors.user_banned_for_reason');
                break
            case ($this->isUnauthorizedException($e)):
                $message =  trans('errors.unauthorized');
                break
            case($e instanceof EntityNotFoundException):
               $message =  trans('errors.not_found_entity');
                break
             case ($e instanceof NotFoundHttpException):
                $message =  trans('errors.not_found');
                break
             case ($e instanceOf MethodNotAllowedHttpException):
                $message =  trans('errors.method_not_allowed');
                break
             default:
                $message =  $e->getMessage(trans('errors.something_wrong'));
                return $message;
        }
     }

     protected function renderExceptionWithWhoops($request,Exception $e)
        {
            $whoops = new \Whoops\Run;

            if ($request->ajax()) {
                $whoops->pushHandler(new \Whoops\Handler\JsonResponseHandler());
            } else {
                $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler());
            }
     
            return new \Illuminate\Http\Response(
                $whoops->handleException($e),
                $this->status,
                $e->getHeaders()
            );    
        }
}





 //if($event->getRequest()->headers->has('content-type') === MediaTypeInterface::JSON_API_MEDIA_TYPE) {
// case(substr($e->getRequest()->getPathInfo(), 0, 4) === '/api') 
//      $response = $this->container->get('api_service')->getErrorResponse($e);
//      $e->setResponse($response);
//      break



