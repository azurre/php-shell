<?php
/**
 * @author: Alex Milenin
 * @email admin@azrr.info
 */

namespace Azurre\Component\System;

/**
 * Class Shell
 */
class Shell
{
    /** No error exit code */
    const NO_ERROR = 0;

    const STDIN  = 0;
    const STDOUT = 1;
    const STDERR = 2;

    /** @var bool run process in background */
    protected $isAsync;

    /** @var string */
    protected $cmd;

    /** @var string The initial working dir for the command. Default - the working dir of the current PHP process. */
    protected $cwd;

    /** @var string STDERR content will be here */
    protected $stdError;

    /** @var string STDOUT content will be here */
    protected $stdOut;

    /** @var int Result code will be here after execution */
    protected $exitCode;

    /** @var int Process ID of current process */
    protected $pid;

    /** @var bool */
    protected $isInProgress;

    /** @var resource|null Resource representing the process */
    protected $process;

    /** @var array */
    protected $pipes = [];

    /** @var float */
    protected $checkTimeout = 0.1;

    /** @var array|null An array with the environment variables for the command that will be run */
    protected $env;

    /**
     * Run command
     *
     * @param string $cmd
     * @param bool $async
     * @return $this
     */
    public function run($cmd, $async = false)
    {
        $this->reset();
        $descriptors = [
            static::STDIN => ['pipe', 'r'],
            static::STDOUT => ['pipe', 'w'],
            static::STDERR => ['pipe', 'w']
        ];
        $this->cmd = $cmd;
        $this->isAsync = $async;
        $this->isInProgress = true;
        $this->process = proc_open($cmd, $descriptors, $this->pipes, $this->getCwd(), $this->getEnv());
        if (!$this->process) {
            return $this;
        }
        if ($async) {
            stream_set_blocking($this->pipes[static::STDOUT], false);
            stream_set_blocking($this->pipes[static::STDERR], false);
        } else {
            $this->stdOut = stream_get_contents($this->pipes[static::STDOUT]);
            $this->stdError = stream_get_contents($this->pipes[static::STDERR]);
            $this->exitCode = proc_close($this->process);
            $this->isInProgress = false;
        }
        return $this;
    }

    /**
     * Run command
     *
     * @param string $cmd
     * @return $this
     */
    public function runAsync($cmd)
    {
        return $this->run($cmd, true);
    }

    /**
     * Reset properties
     *
     * @return $this
     */
    protected function reset()
    {
        $this->isInProgress = false;
        $this->isAsync = false;
        $this->stdError = null;
        $this->stdOut = null;
        $this->exitCode = null;
        $this->cmd = null;
        $this->pid = null;

        return $this;
    }

    /**
     * Is process running asynchronous
     *
     * @return bool
     */
    public function isAsync()
    {
        return $this->isAsync;
    }

    /**
     * Get exit code
     *
     * @return int|null
     */
    public function getExitCode()
    {
        if ($this->isInProgress) {
            return null;
        }
        if (!$this->exitCode) {
            $this->getProcessStatus();
        }
        return $this->exitCode;
    }

    /**
     * Get PID of executed command
     *
     * @return int|null
     */
    public function getPid()
    {
        return $this->pid;
    }

    /**
     * Get last command
     *
     * @return string
     */
    public function getCmd()
    {
        return $this->cmd;
    }

    /**
     * Get environment variables
     *
     * @return array|null
     */
    public function getEnv()
    {
        return $this->env;
    }

    /**
     * Set environment variables
     *
     * @param array $env
     * @return $this
     */
    public function setEnv(array $env)
    {
        $this->env = $env;
        return $this;
    }

    /**
     * Get initial working dir
     *
     * @return string
     */
    public function getCwd()
    {
        return $this->cwd;
    }

    /**
     * Set initial working dir
     *
     * @param string $cwd
     * @return $this
     */
    public function setCwd($cwd)
    {
        $this->cwd = $cwd;
        return $this;
    }

    /**
     * Get errors output of executed command
     *
     * @return string|null
     */
    public function getStdError()
    {
        if (is_resource($this->pipes[static::STDERR]) && $this->isProcessRunning()) {
            $this->stdError .= stream_get_contents($this->pipes[static::STDERR]);
        }
        return $this->stdError;
    }

    /**
     * Get output of executed command
     *
     * @return string
     */
    public function getStdOut()
    {
        if (is_resource($this->pipes[static::STDOUT]) && $this->isProcessRunning()) {
            $this->stdOut .= stream_get_contents($this->pipes[static::STDOUT]);
        }
        return $this->stdOut;
    }

    /**
     * @return float
     */
    public function getCheckTimeout()
    {
        return $this->checkTimeout;
    }

    /**
     * @param float $checkTimeout
     * @return $this
     */
    public function setCheckTimeout($checkTimeout)
    {
        $this->checkTimeout = $checkTimeout * 1;
        return $this;
    }

    /**
     * @param int $pid
     * @return string
     */
    public function getProcessInfo($pid = null)
    {
        $pid = (int)$pid;
        $path = "/proc/{$pid}/status";
        clearstatcache();
        return is_file($path) ? file_get_contents($path) : '';
    }

    /**
     * @return bool
     */
    public function isProcessRunning()
    {
        if ($this->isInProgress && $this->isAsync) {
            $this->getProcessStatus('running');
            if (!$this->isInProgress) {
                $this->stdOut .= stream_get_contents($this->pipes[static::STDOUT]);
                $this->stdError .= stream_get_contents($this->pipes[static::STDERR]);
            }
        }
        return $this->isInProgress;
    }

    /**
     * @param string $element
     * @return array|bool
     */
    public function getProcessStatus($element = null)
    {
        if ($this->isInProgress && $this->isAsync && is_resource($this->process)) {
            $status = proc_get_status($this->process);
            if ($status) {
                $this->pid = $status['pid'];
                $this->isInProgress = $status['running'];
                if (is_numeric($status['exitcode']) && $status['exitcode'] >= 0) {
                    $this->exitCode = $status['exitcode'];
                }
                if ($element && isset($status[$element])) {
                    return $status[$element];
                }
                return $status;
            }
            $this->isInProgress = false;
        }
        return $this->isInProgress;
    }

    /**
     * @return $this
     */
    public function waitForProcess()
    {
        if ($this->isInProgress && $this->isAsync) {
            while ($this->isProcessRunning()) {
                usleep($this->checkTimeout * 1000000);
            }
        }
        return $this;
    }

    /**
     * @return $this
     */
    public static function create()
    {
        return new static;
    }

    /**
     * Close the process
     *
     * @return $this
     */
    public function close()
    {
        if (is_resource($this->process)) {
            proc_close($this->process);
        }

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->getStdOut();
    }

    public function __destruct()
    {
        $this->close();
    }
}
