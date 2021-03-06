<?php

namespace Tools\Generators\Generators;

use App\Models\Keywords;
use App\Models\Posts;
use App\Models\Users;
use Tools\Bots\Twitter;
use Tools\Generators\Generator;
use Tools\Generators\GeneratorInterface;
use Tools\Parsers\Content\BingContentParser;
use Tools\Parsers\Title\BingTitleParser;
use App\Config;

class PostsGenerator extends Generator implements GeneratorInterface
{
    private $bot;

    public function generateElements($result_count):array
    {
        $this->bot = new Twitter();
        $result = [];

        for($i=0; $i < $result_count; $i++)
        {
            $result[] = $this->generateElement();
        }

        return $result;
    }

    protected function generateElement():bool
    {
        $keyword_model = new Keywords();
        $keyword = $keyword_model->getUnused();
        $category_id = $keyword['cid'];
        $category_name = $keyword['cti'];
        $keyword_id = $keyword['_id'];
        $keyword_name = $keyword['ti'];
        $title_length = Config::get('generators')['title_length'];

        $content_parser = new BingContentParser();
        $content_parser = $content_parser->run($keyword, 1, Config::get('generators')['content_length']);

        $content = reset($content_parser);
        $content = empty($content) ? '' : $content;

        $title_parser = new BingTitleParser();
        $title_parser = $title_parser->run($keyword, 1, $title_length);

        if(!isset($title_parser[0])) {
            if(empty($content)) {
                $keyword_model->deleteFromPostGeneration($keyword_id);
                return false;
            }
            $title = substr($content, 0, $title_length);
        } else {
            $title = $title_parser[0];
            $title = empty($title) ? 'error' : $title;
        }

        $author_model = new Users;
        $author = $author_model->getUnused();
        if(empty($author)) {
            throw new \Exception("ERROR - USERS NOT FOUND");
        }
        $author = iterator_to_array($author);
        $author_id = $author['_id'];
        $author['_id'] = (string)$author['_id'];

        $post = new Posts;
        $result = $post->createPost($title, $content, $category_id, $category_name, $keyword_id, $keyword_name, $author);

        $this->bot::send($title, $result['lk']);

        $keyword_model->addPost($keyword_id);
        $author_model->addPost($author_id);
        $GLOBALS['tries'] = 0;

        return true;
    }
}