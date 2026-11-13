<?php


namespace Tobuli\Sensors;


class Tag
{
    /**
     * @var string
     */
    protected $tag_name;

    /**
     * @var string
     */
    protected $regex;

    public function __construct($tag)
    {
        $this->setTagName($tag);
    }

    public function setTagName($tag)
    {
        $this->tag_name = $tag;

        $tagQoute = preg_quote($this->tag_name, '/');
        $this->regex = '/<' . $tagQoute . '>(.*?)<\/' . $tagQoute . '>/s';
    }

    public function getTagName()
    {
        return $this->tag_name;
    }

    public function parse($value)
    {
        // Traccar stores position attributes as JSON; legacy data was XML.
        if (is_string($value)) {
            $trim = ltrim($value);
            if ($trim !== '' && $trim[0] === '{') {
                $decoded = json_decode($trim, true);
                if (is_array($decoded))
                    $value = $decoded;
            }
        }

        if (is_array($value)) {
            $found = $value[$this->tag_name] ?? null;

            if (is_bool($found))
                return $found ? 'true' : 'false';

            return is_scalar($found) ? (string) $found : null;
        }

        preg_match($this->regex, $value, $matches);
        if (isset($matches['1']))
            return $matches['1'];

        return null;
    }


}