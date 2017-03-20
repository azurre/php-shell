<?php
/**
 * @date    05.03.2016
 * @version 0.1
 * @author  Aleksandr Milenin azrr.mail@gmail.com
 */

namespace Azurre\Component\Shell;

class Shell {

    const STDIN    = 0;
    const STDOUT   = 1;
    const STDERR   = 2;

    /**
     * @var string|null The initial working dir for the command. Default - the working dir of the current PHP process.
     */
    protected $cwd;

    /**
     * @var array|null An array with the environment variables for the command that will be run
     */
    protected $env;

    /**
     * @var array|string Place to output STDOUT
     */
    protected $stdout = array('pipe', 'w');

    /**
     * @var array|string Place to output STDERR
     */
    protected $stderr = array('pipe', 'w');

    /**
     * @var bool true - if the proc_open executed successfully
     */
    protected $isExecuted;

    /**
     * @var string STDOUT will be here after execution
     */
    protected $resultOut;
    /**
     * @var string STDERR will be here after execution
     */
    protected $resultErr;

    /**
     * @var int Result code will be here after execution
     */
    protected $resultCode;

    /**
     * @var bool true - run process in background
     */
    protected $isAsync = false;

    /**
     * @var int|null PID will be here after execution
     */
    protected $pid;

    /**
     * @var resource Resource representing the process
     */
    protected $process;

    /**
     * Init self
     *
     * @return $this
     */
    public static function init()
    {
        return new static;
    }

    /**
     * @param string $cmd Command to execute
     *
     * @return array
     */
    public static function cmd($cmd)
    {
        $Shell = (new self)->exec($cmd);
        return [
            'stdout'   => $Shell->getResultOut(),
            'stderr'   => $Shell->getResultError(),
            'errcode'  => $Shell->close()
        ];
    }

    /**
     * Run command (simple)
     *
     * @param string $cmd
     *
     * @return string
     */
    public static function sCmd($cmd)
    {
        $result = static::cmd($cmd);
        if (!empty($result['stdout'])) {
            return $result['stdout'];
        }

        return $result['stderr'];
    }

    /**
     * Run command
     *
     * @param string $cmd
     *
     * @return $this
     */
    public function exec($cmd)
    {
        $this->beforeExec();
        $descSpec = array(
            static::STDIN  => array('pipe', 'r'),
            static::STDOUT => $this->stdout,
            static::STDOUT => $this->isAsync ? array('pipe', 'w') : $this->stdout,
            static::STDERR => $this->stderr
        );

        if ($this->isAsync) {
            $cmd .= ($this->stdout[0] == 'file' ? " >> {$this->stdout[1]}" : '') . ' & echo $!';
        }

        $this->process = @proc_open($cmd, $descSpec, $pipes, $this->cwd, $this->env);
        if (is_resource($this->process)) {
            fclose($pipes[ static::STDIN ]);

            if (isset($pipes[ static::STDOUT ]) && is_resource($pipes[ static::STDOUT ])) {
                $this->resultOut = stream_get_contents($pipes[ static::STDOUT ]);
                if ($this->isAsync) {
                    $this->pid = trim($this->resultOut);
                }

                fclose($pipes[ static::STDOUT ]);
            }

            if (isset($pipes[ static::STDERR ]) && is_resource($pipes[ static::STDERR ])) {
                $this->resultErr = stream_get_contents($pipes[ static::STDERR ]);
                fclose($pipes[ static::STDERR ]);
            }

//            $this->resultCode = proc_close($this->process);
            $this->isExecuted = true;
        } else {
            $error = error_get_last();
            $this->resultCode = 1;
            $this->resultErr  = isset($error['message']) ? $error['message'] : 'Cannot open proc';
            $this->isExecuted = false;
        }

        return $this;
    }

    /**
     * Set place of command output
     *
     * @param array|string $output
     *
     * @return $this
     */
    public function setOutput($output)
    {
        if (is_array($output)) {
            $this->stdout = $output;
        } else {
            $this->stdout = array('file', strval($output), 'a');
        }

        return $this;
    }

    /**
     * Set place of command error output
     *
     * @param array|string $output
     *
     * @return $this
     */
    public function setErrorOutput($output)
    {
        if (is_array($output)) {
            $this->stderr = $output;
        } else {
            $this->stderr = array('file', strval($output), 'a');
        }

        return $this;
    }

    /**
     * Set environment variables
     *
     * @param array $env
     *
     * @return $this
     */
    public function setEnv($env)
    {
        $this->env = $env;

        return $this;
    }

    /**
     * Set initial working dir
     *
     * @param string $dir
     *
     * @return $this
     */
    public function setCwd($dir)
    {
        $this->cwd = $dir;

        return $this;
    }

    /**
     * @return bool Flag if the proc_open executed successfully
     */
    public function isExecuted()
    {
        return $this->isExecuted;
    }

    /**
     * @return bool Flag if the command executed without error (result code == 0)
     */
    public function isSuccess()
    {
        return ($this->isExecuted && $this->resultCode === 0);
    }

    /**
     * Get errors output of executed command
     *
     * @return string
     */
    public function getResultError()
    {
        return $this->resultErr;
    }

    /**
     * Get PID of executed command
     *
     * @return int|null
     */
    public function getPid()
    {
        if (!$this->pid) {
            if (is_resource($this->process)) {
                $status = proc_get_status($this->process);
                if (isset($status['pid'])) {
                    $this->pid = $status['pid'];
                }
            }
        }

        return $this->pid;
    }

    /**
     * Get output of executed command
     *
     * @return string
     */
    public function getResultOut()
    {
        return $this->resultOut;
    }

    /**
     * @return int
     */
    public function getResultCode()
    {
        return $this->resultCode;
    }

    /**
     * Set background mode
     *
     * @param bool $enable
     *
     * @return $this
     */
    public function setAsync($enable = true)
    {
        $this->isAsync = (bool)$enable;

        return $this;
    }

    /**
     * @return array|bool
     */
    public function isProcessRunning()
    {
        if (is_numeric($this->pid)) {
            return posix_getpgid($this->pid) !== false;
        }

        return false;
    }


    /**
     * Run before execute command
     *
     * @return $this
     */
    protected function beforeExec()
    {
        $this->close();

        $this->pid        = null;
        $this->resultOut  = '';
        $this->resultErr  = '';
        $this->process    = null;
        $this->resultCode = 0;
        $this->isExecuted = false;

        return $this;
    }

    /**
     * Close a process opened by proc_open
     *
     * @return int|false Exit code of that process or false
     */
    public function close()
    {
        if (is_resource($this->process)) {
            return proc_close( $this->process );
        }

        return false;
    }

    public function __destruct()
    {
        $this->close();
    }
}