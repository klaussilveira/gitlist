<?php

namespace GitList;

class Config
{
    protected $data;

    public function __construct($file)
    {
        if (!file_exists($file)) {
            die(sprintf('Please, create the %1$s file.', $file));
        }

        $this->data = parse_ini_file($file, true);
        $this->validateOptions();
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
        if (!$this->get('git', 'repositories') || !is_dir($this->get('git', 'repositories'))) {
            die("Please, edit the config file and provide your repositories directory");
        }
    }
}
