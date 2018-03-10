<?php

namespace Tools\Parsers\Comment;

use Tools\Parsers\ParserInterface;

final class RamblerCommentParser extends CommentParser implements ParserInterface
{
    const USER_AGENT = 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/33.0.1750.154 Safari/537.36';

    const LINK_REGEX = '/<a target="_blank" tabindex="2" class="b-serp-item__link" href="(.+?)"/is';

    protected function compareUrl(string $keyword, int $page): string
    {
        return 'https://nova.rambler.ru/search?scroll=1&utm_source=nhp&utm_content=search&utm_medium=button&utm_campaign=self_promo&query='.$keyword.'&page='.$page;
    }
}