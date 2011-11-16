<?php

 class VimeoRetriever extends URLDataRetriever
 {
    protected $DEFAULT_PARSER_CLASS='VimeoDataParser';
    
    protected function init($args) {
        parent::init($args);

        if (isset($args['CHANNEL']) && strlen($args['CHANNEL'])) {
            $this->setOption('channel', $args['CHANNEL']);
        }
    }

    public function url() {
        $url = 'http://vimeo.com/api/v2/';
        
        if ($author = $this->getOption('author')) {
            $url .= $author;
        } elseif ($channel = $this->getOption('channel')) {
            $url .= 'channel/' . $channel;
        } else {
            throw new KurogoConfigurationException("Unable to determine type of request");
        }
        
        $url .= "/videos.json";
        return $url;
    }
    
    protected function isValidID($id) {
        return preg_match("/^[0-9]+$/", $id);
    }
    
	 // retrieves video based on its id
	public function getItem($id) {
	    if (!$this->isValidID($id)) {
	        return false;
	    }

        $url = 'http://vimeo.com/api/v2/video/' . $id . '.json';
        $this->setBaseURL($url);
        if ($items = $this->getParsedData()) {
            return isset($items[0]) ? $items[0] : false;
        }
        
        return false;
	}
}
 
class VimeoDataParser extends DataParser
{
    protected function parseEntry($entry) {
        $video = new VimeoVideoObject();
        $video->setURL($entry['url']);
        if (isset($entry['mobile_url'])) {
            $video->setMobileURL($entry['mobile_url']);
        }
        $video->setTitle($entry['title']);
        $video->setDescription($entry['description']);
        $video->setDuration($entry['duration']);
        $video->setID($entry['id']);
        $video->setImage($entry['thumbnail_small']);
        $video->setStillFrameImage($entry['thumbnail_large']);
        
        if (isset($entry['tags'])) {
            $video->setTags($entry['tags']);
        }
        
        $video->setAuthor($entry['user_name']);
        $published = new DateTime($entry['upload_date']);
        $video->setPublished($published);
        return $video;
    }
    
    public function parseData($data) {
        if ($data = json_decode($data, true)) {
            
            $videos = array();
            if (is_array($data)) {
                if (isset($data['id'])) {
                } else {
                    $this->setTotalItems(count($data));
                    foreach ($data as $entry) {
                        $videos[] = $this->parseEntry($entry);
                    }
                    
                    return $videos;
                }                
            }
        } 

        return array();
        
    }
}

class VimeoVideoObject extends VideoObject
{
    protected $type = 'vimeo';

    public function canPlay(DeviceClassifier $deviceClassifier) {
        if (in_array($deviceClassifier->getPlatform(), array('blackberry','bbplus'))) {
            return false;
        }

        return true;
    }
}