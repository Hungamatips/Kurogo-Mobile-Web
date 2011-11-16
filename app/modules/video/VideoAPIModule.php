<?php

Kurogo::includePackage('Video');

class VideoAPIModule extends APIModule {    
    protected $id='video';  // this affects which .ini is loaded
    protected $vmin = 1;
    protected $vmax = 1;
    protected $feeds = array();
    protected $legacyController = false;
    protected static $defaultModel = 'VideoDataModel';
    protected static $defaultController = 'VideoDataController';

    protected function arrayFromVideo($video) {
        return array(
            "id"              => $video->getID(),
            "title"           => $video->getTitle(),
            "description"     => strip_tags($video->getDescription()),
            "author"          => $video->getAuthor(),
            "published"       => $video->getPublished(),
            "date"            => $video->getPublished()->format('M n, Y'),
            "url"             => $video->getURL(),
            "image"           => $video->getImage(),
            "width"           => $video->getWidth(),
            "height"          => $video->getHeight(),
            "duration"        => $video->getDuration(),
            "tags"            => $video->getTags(),
            "mobileURL"       => $video->getMobileURL(),
            "streamingURL"    => $video->getStreamingURL(),
            "stillFrameImage" => $video->getStillFrameImage(),
            );
    }

    protected function getFeed($feed=null) {
        $feed = isset($this->feeds[$feed]) ? $feed : $this->getDefaultSection();
        $feedData = $this->feeds[$feed];

        try {
            if (isset($feedData['CONTROLLER_CLASS'])) {
                $modelClass = $feedData['CONTROLLER_CLASS'];
            } else {
                $modelClass = isset($feedData['MODEL_CLASS']) ? $feedData['MODEL_CLASS'] : self::$defaultModel;
            }
            
            $controller = VideoDataModel::factory($modelClass, $feedData);
        } catch (KurogoException $e) { 
            $controller = VideoDataController::factory($feedData['CONTROLLER_CLASS'], $feedData);
            $this->legacyController = true;
        }

        return $controller;
    }

    protected function getDefaultSection() {
        return key($this->feeds);
    }

    public function initializeForCommand() {
        $this->feeds = $this->loadFeedData();

        switch ($this->command) {
        case 'sections':
            $this->setResponse(VideoModuleUtils::getSectionsFromFeeds($this->feeds));
            $this->setResponseVersion(1);                
            break;
        case 'videos':
        case 'search':            
            // videos commands requires one argument: section.
            // search requires two arguments: section and q (query).
            $section = $this->getArg('section');

            $controller = $this->getFeed($section);
            $videos = array();

            // TODO: this isn't the right place to hard code paging limits

            if ($this->command == 'search') {
                $limit = 20;
                $query = $this->getArg('q');                
                if ($this->legacyController) {
                    $items = $controller->search($query, 0, $limit);
                } else {
                    $controller->setLimit($limit);
                    $items = $controller->search($query);
                }
            }
            else {
                $limit = 50;
                if ($this->legacyController) {
                    $items = $controller->items(0, 50);
                } else {
                    $controller->setLimit($limit);
                    $items = $controller->items();
                }
            }

            foreach ($items as $video) {
                $videos[] = $this->arrayFromVideo($video);
            }

            $this->setResponse($videos);
            $this->setResponseVersion(1);                
            break; 
                
        case 'detail':
            $section = $this->getArg('section');
            $controller = $this->getFeed($section);
            $videoid = $this->getArg('videoid');

            if ($video = $controller->getItem($videoid)) {
                $result = $this->arrayFromVideo($video);
                $this->setResponse($result);
                $this->setResponseVersion(1);
            } else {
                $this->throwError(new KurogoError("Video Not Found"));
            }
                        
            break;
        default:
            $this->invalidCommand();
            break;
        }
    }
}
