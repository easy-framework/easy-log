<?php
/****************************************************
 *                    Easy Log                      *
 *                                                  *
 *                    TIERGB                        *
 *           <https://github.com/TIGERB>            *
 *                                                  *
 ****************************************************/

namespace Easy;

use Exception;

/**
 * log class
 * 
 * 特点：相比其他日志类，一起请求只会最终打印一次日志从而降低到一次磁盘I/O
 * 
 * 使用说明：
 * Log::debug(...)　-> debug日志
 * Log::notice(...) ->　提醒日志
 * Log::warning(...) -> 警告日志
 * Log::error(...)  -> 错误日志　
 */
class Log
{
	/**
	 * log buffer
	 *
	 * @var array
	 */
    private $buffer = [
        "\n"
        // "\n---date---|---level---|---pid---|---memeory---|---log---\n"
    ];
    

    /**
	 * log method support
	 *
	 * @var array
	 */
    private $methodSupport = [
        'debug',
        'notice',
        'warning',
        'error'
    ];
    
    /**
	 * class private variable change allow list
	 *
	 * @var array
	 */
	private $variablesAllow = [
        'logPath',
        'logFileSize',
        'logFileName'
    ];

	/**
	 * the log file name
	 *
	 * @var string
	 */
    private $logFileName = '';

    /**
	 * the final log name include the path
	 *
	 * @var string
	 */
    private $finalFileName = '';

    /**
	 * the log path
	 *
	 * @var string
	 */
    private $logPath = '/tmp/easy';

    /**
	 * the log file size
	 *
	 * @var int unit/M
	 */
    private $logFileSize = 512;

    /**
	 * log
	 *
	 * @var string
	 */
    private $log = '';

    /**
     * instance
     * 
     * @var object
     */
    private static $_instance;
  
    /**
     * construct function
     * 
     * @return void
     */
    private function __construct()
    {
        register_shutdown_function([$this, 'write']);
    }

    /**
     * set log path function
     * 
     * @return void
     */
    public function __set($name = '', $value = '')
    {
        if (! in_array($name, $this->variablesAllow)) {
            throw new Exception('Operate is forbidden for this variable ' . $name, 401);    
        }
        $this->$name = $value;
    }
    
    /**
     * the magic function
     * clone is forbidden
     * 
     * @return string
     */
    public function __clone()
    {
        throw new Exception('Clone is forbidden', 401);
    }
  
    /**
     * get instance
     * 
     * @return object
     */
    public static function getInstance()
    {
      if (!self::$_instance instanceof self) {
        self::$_instance = new self;
      }
      return self::$_instance;
    }
	
	/**
	 * the magic __callStatics function
	 *
	 * @param string $method
	 * @param array $log
	 * @return void
	 */
	public static function __callstatic($method = '', $log = [])
	{
        $instance = self::getInstance();
		if (! in_array($method, $instance->methodSupport)) {
			throw new Exception('log method not support', 500);
		}
        $instance->decorate($method, $log);
		$instance->pushLog();
	}

	/**
	 * decorate log msg
	 *
	 * @param string $rank
	 * @param array $log
	 * @return void
	 */
	private function decorate($rank = 'info', $log = [])
	{
        if (! $log) {
            $log = [];
        }
		$time        = date('Y-m-d H: i: s', time());
		$pid         = posix_getpid();
		$memoryUsage = round(memory_get_usage()/1024, 2) . ' kb';
		switch ($rank) {
            case 'debug':
                $rank = "\033[32m{$rank}\033[0m";
            break;
			case 'notice':
				$rank = "\033[36m{$rank} \033[0m";
			break;
			case 'warning':
				$rank = "\033[33m{$rank}\033[0m";
            break;
            case 'error':
                $rank = "\033[31m{$rank}\033[0m";
            break;
			
			default:
			
			break;
        }
        
		$default = [
			$time,
			$rank,
			$pid,
			$memoryUsage
        ];
        
        if ($log) {
            foreach ($log as &$v) {
                if (is_array($v)) {
                    if (defined('JSON_UNESCAPED_UNICODE')) {
                        $v = json_encode($v, JSON_UNESCAPED_UNICODE);
                    } else {
                        $v = json_encode($v);
                    }
                }
            }
            unset($v);
        }

		$log  = array_merge($default, $log);
        $tmp  = '';
		foreach ($log as $k => $v) {
			if ($k === 0) {
				$tmp = "{$v}";
				continue;
			}
			$tmp .= " | {$v}";
        }
        $this->log = $tmp;
    }

    /**
     * the finally write
     */
    public function write()
    {
        if (! $this->buffer) {
            return;
        }
        $msg = '';
        foreach ($this->buffer as $v) {
            $msg .= $v . PHP_EOL; 
        }

        // check file path
        if (! file_exists($this->logPath)) {
            mkdir($this->logPath, 0777, true);
        }
        $this->finalFileName = $this->logPath . "{$this->logFileName}." . date('Y-m-d', time()) . '.log';

        // check file size
        if (file_exists($this->finalFileName)) {
            $filesize = filesize(realpath($this->finalFileName));
            if ($filesize >= $this->logFileSize*1024*1024) {
                $this->finalFileName = $this->logPath . "{$this->logFileName}." . date('Y-m-d', time());
                $this->finalFileName .= '.' . date('H-i-s', time()) . '.log';
            }
    
        }
            
        // write
        error_log(
            $msg, 
            3, 
            $this->finalFileName
        );
    }

    /**
     * push the log msg to the buffer
     */
    public function pushLog()
    {
        $this->buffer[] = $this->log;
    }
}
