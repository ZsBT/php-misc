<?php
/**
 * Implements threading in PHP
 * 
 * @package <none>
 * @version 1.0.0 - stable
 * @author Tudor Barbu <miau@motane.lu>
 * @copyright MIT
 */
class zsThread {
    const FUNCTION_NOT_CALLABLE     = 10;
    const COULD_NOT_FORK            = 15;
    
    private $errors = array(
        zsThread::FUNCTION_NOT_CALLABLE   => 'You must specify a valid function name that can be called from the current scope.',
        zsThread::COULD_NOT_FORK          => 'pcntl_fork() returned a status of -1. No new process was created',
    );
    
    protected $runnable;
    private $pid;

    public static function available() {
        $required_functions = array('pcntl_fork',);
        foreach( $required_functions as $function ) {
            if ( !function_exists( $function ) ) return false;
        }
        return true;
    }
    
    public function __construct( $_runnable = null ) {
    	if( $_runnable !== null ) {
        	$this->setRunnable( $_runnable );
    	}
    }
    
    public function setRunnable( $_runnable ) {
        if( self::runnableOk( $_runnable ) ) {
            $this->runnable = $_runnable;
        }
        else {
            throw new Exception( $this->getError( zsThread::FUNCTION_NOT_CALLABLE ), zsThread::FUNCTION_NOT_CALLABLE );
        }
    }
    
    public function getRunnable() {return $this->runnable;}
    
    public static function runnableOk( $_runnable ) {return ( function_exists( $_runnable ) && is_callable( $_runnable ) );}
    
    public function getPid() {return $this->pid;}
    
    public function isAlive() {$pid = pcntl_waitpid( $this->pid, $status, WNOHANG );return ( $pid === 0 );}
    
    public function start() {
        $pid = @ pcntl_fork();
        if( $pid == -1 ) {throw new Exception( $this->getError( zsThread::COULD_NOT_FORK ), zsThread::COULD_NOT_FORK );}
        if( $pid ) {$this->pid = $pid;}
        else {
            pcntl_signal( SIGTERM, array( $this, 'signalHandler' ) );
            $arguments = func_get_args();
            if ( !empty( $arguments ) ) {call_user_func_array( $this->runnable, $arguments );}
            else {call_user_func( $this->runnable );}
            exit( 0 );
        }
    }
    
    public function stop( $_signal = SIGKILL, $_wait = false ) {
        if( $this->isAlive() ) {
            posix_kill( $this->pid, $_signal );
            if( $_wait ) {
                pcntl_waitpid( $this->pid, $status = 0 );
            }
        }
    }
    
    public function kill( $_signal = SIGKILL, $_wait = false ) {return $this->stop( $_signal, $_wait );}
    
    public function getError( $_code ) {
        if ( isset( $this->errors[$_code] ) ) {return $this->errors[$_code];}
        else {return 'No such error code ' . $_code . '! Quit inventing errors!!!';}
    }
    
    protected function signalHandler( $_signal ) {
        switch( $_signal ) {
            case SIGTERM:
                exit( 0 );
            break;
        }
    }
}

if( ! zsThread::available() ) die( 'Threads not supported' );

// EOF
