<?php
/**
 * Parser Reflection API
 *
 * @copyright Copyright 2016, Lisachenko Alexander <lisachenko.it@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 */
namespace Go\ParserReflection\TestingSupport;

/**
 * Basically a wrapper for preg_replace so transforms can be
 * easily passed, stored and repeated.
 */
class TextTransformer
{
    private $transforms;

    public function __construct(array $transforms = [])
    {
        $this->transforms = $transforms;
    }

    public function filter($in)
    {
        $out = $in;
        foreach ($this->transforms as $transformPair) {
            $out = preg_replace($transformPair[0], $transformPair[1], $out);
        }
        return $out;
    }

    public function __invoke($in)
    {
        return $this->filter($in);
    }
}
