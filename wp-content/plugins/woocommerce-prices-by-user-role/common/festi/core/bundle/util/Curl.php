<?php 

class Curl extends Entity
{
    /**
     * Reference to a CURL connection
     * 
     * @var resource
     */
    protected $curl;

    private $_lastCode = false;
    private $_lastHeaders = array();
    
    public function __construct($options = array())
    {
        if (!extension_loaded('curl')) {
            throw new Exception(_('cURL extension is not available on your server'));
        }
        
        $default = $this->getDefaultOptions();
        foreach ($default as $index => $value) {
            if (isset($options[$index])) {
                continue;
            }
            $options[$index] = $value;
        }
        
        $this->options = $options;
        
        $this->curl = curl_init();
    } // end __construct
    
    /**
     * Returns the default options
     */
    protected function getDefaultOptions()
    {
        $curlOptions = array(
            CURLOPT_FAILONERROR    => 1,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_USERAGENT      => "Mozilla/5.0 (Windows; U; Windows NT 5.1; ru; rv:1.9.2.15)".
                                      " Gecko/20110303 Firefox/3.6.15 GTB7.1",
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_VERBOSE        => false,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_CONNECTTIMEOUT => 60,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_HTTPHEADER     => array('Expect:')
        );
        
        return $curlOptions;
    } // end getDefaultOptions
    
    /**
     * Returns the content of url
     *
     * @param string $url
     * @param array|bool $postParams post request params
     * @param string|bool $proxy proxy host
     * @param string|bool $proxyPswd proxy password
     * @param boolean $isIgnoreErrors
     *
     * @return string
     */
    public function getUrl(
        $url, $postParams = false, $proxy = false, $proxyPswd = false, $isIgnoreErrors = false
    )
    {
        $this->options[CURLOPT_URL] = $url;
        
        if ($proxy) {
            $this->options[CURLOPT_PROXY] = $proxy;
        }
        
        if ($proxyPswd) {
            $this->options[CURLOPT_PROXYUSERPWD] = $proxyPswd;
        }
        
        if ($postParams) {
            $this->options[CURLOPT_POST] = true;
            $this->options[CURLOPT_POSTFIELDS] = $postParams;
        }

        curl_setopt_array($this->curl, $this->options);

        //
        $this->_lastHeaders = array();

        $headers = &$this->_lastHeaders;
        curl_setopt(
            $this->curl,
            CURLOPT_HEADERFUNCTION,
            static function($curl, $header) use (&$headers)
            {
                $len = strlen($header);
                $header = explode(':', $header, 2);
                if (count($header) < 2) // ignore invalid headers
                    return $len;

                $headers[strtolower(trim($header[0]))][] = trim($header[1]);

                return $len;
            }
        );

        $res = curl_exec($this->curl);
        $code = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);

        $this->_lastCode = $code;

        if ($code >= 400 && !$isIgnoreErrors) {
            return false;
        }
    
        return $res;
    } // end getUrl

    public function getCode()
    {
        return $this->_lastCode;
    }

    /**
     * Returns headers of remote response.
     *
     * @return array
     */
    public function getResponseHeaders(): array
    {
        return $this->_lastHeaders;
    } // end getHeaders
    
    public function addHeader($header)
    {
        if (array_key_exists(CURLOPT_HTTPHEADER, $this->options)) {
            $this->options[CURLOPT_HTTPHEADER][] = $header;
        } else {
            $this->options[CURLOPT_HTTPHEADER] = array($header);
        }
        
    } // end addHeader

    public function getInfo($infoCode)
    {
        return curl_getinfo($this->curl, $infoCode);
    }
    
    public function setOptions($index, $value = null)
    {
        if (is_null($value) && is_array($index)) {
            $this->options += $index;
        } else {
            $this->options[$index] = $value;
        }
    } // end setOptions
    
    public function __destruct()
    {
        if (is_resource($this->curl)) {
            curl_close($this->curl);
        }
        $this->curl = null;
        //unset($this->curl);
    } // end __destruct
    
}
