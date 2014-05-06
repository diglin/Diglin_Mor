<?php
class Diglin_Mor_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Generate URL MOR
     * 
     * @return string
     */
    public function getMorUrl ()
    {
        $ip = Mage::getStoreConfig('mor/configuration/ip_address');
        if (empty($ip)) {
            Mage::throwException($this->__('The address IP of your MOR installation is not configured.'));
        }
        if (Mage::getStoreConfigFlag('mor/configuration/secure')) {
            return 'https://' . $ip;
        }
        return 'http://' . $ip;
    }
    
    /**
     * Generate sha1 url parameters
     * 
     * @param string $vars
     */
    public function generateHashKey($vars){
        $apikey = Mage::getStoreConfig('mor/configuration/api_secret_key');
        if(empty($apikey)){
            Mage::throwException($this->__('The MOR API key has not been yet set.'));
        }
        
        $hash = implode('', $vars) . $apikey;
        return sha1($hash);
    }
    
    /**
     * Create url parameters string
     * 
     * @param array $vars
     */ 
    public function createParamsString(array $vars = array()){
        $str = array();
        
        foreach ($vars as $key => $value){
            $str[] = $key.'='.$value;
        }
        
        //return urlencode(implode('&', $str));
        return implode('&', $str);
    }
    
    /**
     * Call MOR API
     * 
     * @param string $path
     * @param array|Varien_Object $vars
     * @return Varien_Simplexml_Element
     */
    public function callMorApi($path, $vars){
        
        if(!is_array($vars) && !($vars instanceof Varien_Object)){
            $vars = array($vars);
        }elseif($vars instanceof Varien_Object){
            $vars = $vars->__toArray();
        }
        
        $params = $this->createParamsString($vars);
        $path = '/' . trim($path,'/') . '/';
        
        $url = $this->getMorUrl() . $path;
        
        $curl = new Varien_Http_Adapter_Curl();
        $curl->write('POST', $url, null, array(), $params);
        $data = $curl->read();
        
        if($curl->getErrno()){
            Mage::log('Curl error: ' . $curl->getError() . ' - Curl info ' . $curl->getInfo());
            Mage::throwException($this->__('An error occured while communicating with MOR.'));
            $curl->close();
            return;
        }
        
        $curl->close();
        
        try{
            $response = Zend_Http_Response::fromString($data);
            return new Varien_Simplexml_Element($response->getBody());
        }catch (Exception $e){
            Mage::log('Diglin_Mor_Helper_Data::callMorApi the response is not XML for the url' . $url . ' with the params' . $params);
            Mage::throwException($this->__('An error occured while communicating with MOR.'));
        }
    }
}