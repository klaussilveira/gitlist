<?php

namespace GitList;

class Config
{
    protected $data;

    public static function fromFile($file) {
        if (!file_exists($file)) {
            die(sprintf('Please, create the %1$s file.', $file));
        }
        $data = parse_ini_file($file, true);
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
		$at_least_one_ok = false;
		$at_least_one_wrong = false;

        foreach ( $this->get('git', 'repositories') as $dir ) {
	        if (!$dir || !is_dir($dir)) {
				$at_least_one_wrong = true;
			} else {
				$at_least_one_ok = true;
			}
        }

		if ( !$at_least_one_ok  ) {
			die("Please, edit the config file and provide your repositories directory");
		}

		if ( $at_least_one_wrong  ) {
			die("One or more of the supplied repository paths appears to be wrong. Please, check the config file");
		}
    }
}
