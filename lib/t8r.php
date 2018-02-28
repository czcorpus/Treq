<?php
/**
 * Provides text translation capabilities. T8r stands for Translator.
 * @brief Translator
 * @class T8r
 * @author Tomas Capka
 * @date 2013-02-03
 */
class T8r
{
	/**
	 * Gets translation of text, according to current language and domain
	 * @param text
	 * @return Translated text
	 * @retval string
	 */
	public static function tr($text)
	{
		if (!array_key_exists(self::$lang, self::$translations))
			return $text;

		$langArr = self::$translations[self::$lang];
		if (!array_key_exists(self::$domain, $langArr))
			return $text;

		$domArr = $langArr[self::$domain];
		if (!array_key_exists($text, $domArr))
			return $text;

		return $domArr[$text];
	}

	/**
	 * Gets current domain
	 * @return Current domain
	 * @retval string
	 */
	public static function getDomain()
	{
		return self::$domain;
	}

	/**
	 * Sets domain to use
	 * @param domain
	 */
	public static function setDomain($domain)
	{
		self::$domain = $domain;
	}

	/**
	 * Gets current language
	 * @return Current language
	 * @retval string
	 */
	public static function getLang()
	{
		return self::$lang;
	}

	/**
	 * Sets current language code
	 * @param lang Current language ISO code
	 */
	public static function setLang($lang)
	{
		self::$lang = $lang;
	}

	/**
	 * Loads translations and merges them with current ones
	 * @param translations Array of type arr[lang][domain][text]=translation
	 */
	public static function loadTranslations($translations)
	{
		self::$translations = self::arrayUnionRecursive(self::$translations, $translations);
	}
	
	/**
	 * Recursive array union
	 * @param array1 First array
	 * @param array2 Second array
	 */
	private static function arrayUnionRecursive($array1, $array2)
	{
		foreach ($array2 as $key => $value)
		{
			if(is_array($value))
			{
				if (!array_key_exists($key, $array1))
				{
					$array1[$key] = array();
				}
				$array1[$key] = self::arrayUnionRecursive($array1[$key], $array2[$key]);
			}
			else
			{
				$array1[$key] = $value;
			}
		}
		return $array1;
	}

	private static $translations = array();
	private static $lang = NULL;
	private static $domain = 'global';
}
?>
