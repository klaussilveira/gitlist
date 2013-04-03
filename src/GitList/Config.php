<?php

namespace GitList;

class Config
{
    protected $data;

    public static function fromFile($file)
    {
        if (!file_exists($file)) {
            die(sprintf('Please, create the %1$s file.', $file));
        }

        $data = parse_ini_file($file, true);
        
        # Ensure that repositories item is an array
        if (!is_array($data['git']['repositories'])) {
            $data['git']['repositories'] = array($data['git']['repositories']);
        }

        return new static($data);
    }

    public function __construct($data)
    {
        $this->data = $data;
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
        $repositories = $this->get('git', 'repositories');

        if (!is_array($repositories)) {
            return;
        }

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
            die("Please, edit the config file and provide your repositories directory");
        }

        if ($atLeastOneWrong) {
            die("One or more of the supplied repository paths appears to be wrong. Please, check the config file");
        }
    }
}

