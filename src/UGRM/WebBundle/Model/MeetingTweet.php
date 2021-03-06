<?php

namespace UGRM\WebBundle\Model;

use UGRM\DataBundle\Model\Location;

class MeetingTweet
{
    /**
     * @var \UGRM\DataBundle\Model\Meeting
     */
    private $meeting;

    /**
     * @var string
     */
    private $host;

    private $tweet = '';

    private $tweetlen = 0;

    public function __construct(\UGRM\DataBundle\Model\Meeting $meeting, $host)
    {
        $this->meeting = $meeting;
        $this->host = $host;
    }

    function addToTweet($str, $strlen = null)
    {
        $thelen = $this->tweetlen + ($strlen ? $strlen : strlen($str));
        if ($thelen > 140) return;
        $this->tweet .= $str;
        $this->tweetlen = $thelen;
    }

    protected function reset()
    {
        $this->tweet = '';
        $this->tweetlen = 0;
    }

    /**
     * @return string
     */
    public function getTweet()
    {
        $this->reset();
        if ($this->meeting->time->isToday()) {
            $this->addToTweet('Heute');
        } elseif ($this->meeting->time->isTomorrow()) {
            $this->addToTweet('Morgen');
        } else {
            $this->addToTweet('Am ' . strftime('%a, %d.%m', $this->meeting->time->format('U')));
        }
        $prefix = $this->meeting->usergroup->female || $this->meeting->usergroup->plural ? 'die ' : 'der ';
        $n = $this->getName();
        $this->addToTweet(($this->meeting->usergroup->plural ? ' treffen sich ' : ' trifft sich ') . $prefix . $n);
        $this->addToTweet(' um ' . strftime('%H:%M Uhr', $this->meeting->time->format('U')));
        if ($this->meeting->location && $this->meeting->location instanceof Location) {
            $this->addToTweet(' in ' . $this->meeting->location->city);
            if ($this->meeting->location->twitter) $this->addToTweet(' ' . $this->meeting->location->twitter);
        }
        $this->addToTweet(' ' . sprintf('http://%s/~%s', $this->host, $this->meeting->usergroup->id), 22);
        $ht = $this->meeting->usergroup->hashtag;
        if ($ht && $ht != $n && substr($ht, 1) != substr($n, 1)) $this->addToTweet(' ' . $ht);
        foreach ($this->meeting->usergroup->tags as $tag) {
            $this->addToTweet(' #' . preg_replace('/[^a-zA-Z0-9]/', '', $tag));
        }
        $this->addToTweet(' #ugrm');
        return $this->tweet;
    }

    protected function getName()
    {
        if ($n = $this->meeting->usergroup->twitter) return $n;
        if ($n = $this->meeting->usergroup->hashtag) return $n;
        if ($n = $this->meeting->usergroup->nickname) return $n;
        return $this->meeting->usergroup->name;
    }

    public function getLink()
    {
        $url = 'https://twitter.com/intent/tweet';
        $params = array(
            'original_referer' => sprintf('http://%s/', $this->host),
            'text' => $this->getTweet());
        return $url . '?' . http_build_query($params);
    }
}
