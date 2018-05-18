<?php

namespace GitList;

class Config
{
    protected $data;

    public function __construct($data = array())
    {
        $this->data = $data;
    }

    public static function fromFile($file)
    {
        if (!file_exists($file)) {
            die(sprintf('Please, create the %1$s file.', $file));
        }

        $data = parse_ini_file($file, true);
        $config = new static($data);
        $config->validateOptions();

        return $config;
    }

    public function get($section, $option)
    {
        if (!array_key_exists($section, $this->data)) {
            return false;
        }

        if (!array_key_exists($option, $this->data[$section])) {
            return false;
        }

        return $this->data[$section][$option];
    }

    public function getSection($section)
    {
        if (!array_key_exists($section, $this->data)) {
            return false;
        }

        return $this->data[$section];
    }

    public function set($section, $option, $value)
    {
        $this->data[$section][$option] = $value;
    }

    protected function validateOptions()
    {
        $repositories = $this->get('git', 'repositories');

        $atLeastOneOk = false;
        $atLeastOneWrong = false;

        foreach ($repositories as $directory) {
            if (!$directory || !is_dir($directory)) {
                $atLeastOneWrong = true;
            } else {
                $atLeastOneOk = true;
            }
        }

        if (!$atLeastOneOk) {
            die('Please, edit the config file and provide your repositories directory');
        }

        if ($atLeastOneWrong) {
            die('One or more of the supplied repository paths appears to be wrong. Please, check the config file');
        }
    }
}
