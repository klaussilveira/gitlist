<?php

namespace GitList\Util;

class Encoder
{
    /**
     * helper to encodes non UTF-8 text-files
     *
     * @param  string $spec Path spec
     *
     * @return specified encoded (mostly UTF-8) text
     */
    public static function encode_text($text, $options)
    {
		if ($options['encoding.enable']) {
			$encoding = mb_detect_encoding($text, $options['encoding.detect_order']);

			if (!$encoding) {
				if ($options['encoding.search_all']) {
					// search all encodings
					$encoding = mb_detect_encoding($text, mb_list_encodings());
				}
				if (!$encoding) {
					// last resort
					$encoding = $options['encoding.fallback'];
				}
			}

			return mb_convert_encoding($text, $options['encoding.convert_to'], $encoding);				
		}
		return $text;
    }

}
