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
        $config = new static($data);
        $config->validateOptions();

        return $config;
    }

    public function __construct($data = array())
    {
        $this->data = $data;
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

		if(!empty($repositories)){
	        foreach ($repositories as $directory) {
	            if (!$directory || !is_dir($directory)) {
	                $atLeastOneWrong = true;
	            } else {
	                $atLeastOneOk = true;
	            }
	        }
		}

        if (!$atLeastOneOk) {
            die("Please, edit the config file and provide your repositories directory");
        }

        if ($atLeastOneWrong) {
            die("One or more of the supplied repository paths appears to be wrong. Please, check the config file");
        }
    }
	
	public function toFile($file)
	{
		$this->write_ini_file($this->data, $file);
	}
	
	private function write_ini_file($assoc_arr, $path) 
	{ 
	    $content = ""; 

        foreach ($assoc_arr as $key=>$elem) { 
            $content .= "[".$key."]\n"; 
            foreach ($elem as $key2=>$elem2) { 
                if(is_array($elem2)) 
                { 
                    for($i=0;$i<count($elem2);$i++) 
                    {
                    	if(isset($elem2[$i])){
	                    	# write true or false as keywords without absostrophe
	                    	if($elem2[$i] == 'true' || $elem2[$i] == 'false'){
								$content .= $key2."[] = ".$elem2[$i]."\n"; 
							}  else {
								$content .= $key2."[] = '".$elem2[$i]."'\n"; 
							}						
                    	}
                    } 
                } 
                else if($elem2==""){
                	$content .= $key2." = \n";
                }  
                else{
                	# write true or false as keywords without absostrophe
					if($elem2 == 'true' || $elem2 == 'false'){
						$content .= $key2." = ".$elem2."\n";
					}  else {
						$content .= $key2." = '".$elem2."'\n";
					}
				} 
            } 
        }
	  
	
	    if (!$handle = fopen($path, 'w')) { 
	        return false; 
	    } 
	    if (!fwrite($handle, $content)) { 
	        return false; 
	    } 
	    fclose($handle); 
	    return true; 
	}	

	public function getData(){
		return $this->data;
	}
	
	public function addAndOverwrite($data_array){
		$this->data = array_merge($this->data, $data_array);
	}
}

