<?php
namespace Tools\Parsers\Title;

use App\Models\Keywords;
use Tools\DuplicatesValidators\Validators\TitlesDuplicatesValidator;
use Tools\Parsers\Parser;
//парсер тайтлов
abstract class TitleParser extends Parser
{
    private $results_count;
    private $page;
    private $results_per_page;
    private $keyword;
    private $length;

    const LENGTH_PER_PAGE = 50;

    abstract protected function compareUrl(string $keyword, int $page);

    public function run($keyword, $result_count, $length, $page=0)//отдаём результат парса через эту функцию
    {
        $this->results_count = $result_count;
        $this->page = $page + $keyword['po'.Keywords::PAGE_OFFSET_TITLE];
        $this->keyword = $keyword;
        $this->length = $length;

        $this->results_per_page = (int) ceil(($length/self::LENGTH_PER_PAGE) * $result_count);

        $titles = $this->parse();//парсим тайтлы

        $titles = array_slice($titles, 0, $this->results_count);//берём только необходимое количество
        return $titles;
    }

    protected function parse()//парсим тайтлы с page страницы
    {
        $result = [];
        $tries = 0;
        $parse_tries = 0;
//        $links_validator = new TitlesDuplicatesValidator();
        $keyword_model = new Keywords();
        $next_title = '';

        while(count($result) < $this->results_count && $parse_tries < 5)//пока не получим нужное кличество тайтлов или пока не накапает 5 попыток
        {
            if(!empty($next_title)) {
                $new_title = $next_title;
                $next_title = '';
            } else{
                $new_title = '';
            }

            while(mb_strlen($new_title) < $this->length && $tries < 5) {
                $url = $this->compareUrl($this->keyword['ti'], $this->page);
                $data = $this->request($url, $this->results_per_page);//делаем запрос к поисковику
                if(!$data) {
                    break 1;
                }

                //$titles = $links_validator->validate($links_data); //прячем на будущее
                $keyword_model->addPageOffset($this->keyword['_id'], Keywords::PAGE_OFFSET_TITLE); //повышаем отступ в страницах парса для этого ключевика

                foreach ($data as $title) {
                    $title = $this->validate($title['name']);
                    if($title){
                        if(mb_strlen($new_title) >= $this->length) {
                            $next_title = $title;
                            break 1;
                        }
                        $new_title .= ' - ' . $title;
                    }
                }
                $tries++;
                $this->page++;
            }
            if(mb_strlen($new_title) >= $this->length) {
                $result[] = substr($new_title, 3);
            } else {
                $parse_tries++;
                break;
            }
            $tries = 0;
        }

        return $result;
    }

    protected function validate($title)//валидаця тайтлов
    {
        $title = preg_replace('/([-_])/', '', $title);
        $title = preg_replace('/^([\.,])/', '', $title);
        $title = preg_replace('/(pdf.*)$/', '', $title);
        $title = preg_replace('/(ООО ".*")/u', '', $title);
        $title = preg_replace('/(\d{2}\.\d{2}\.\d{4})/', '', $title);
        $title = preg_replace('/(\|)/', '', $title);
        $title = preg_replace('/(\…)/u', '', $title);
        $title = preg_replace('/(\.){2,}/', '', $title);
        $title = preg_replace('/[a-zA-Z\-\.0-9]*(href|url|http|www|\.ru|\.com|\.net|\.info|\.org|\.ua|\.by)/i', '', $title);
        $title = trim($title);
        $title = preg_replace('/(\s.)$/u', '', $title);

//        if(substr_count($title, ' ') <= 2) {
//            return false;
//        }
        if(!preg_match('/([а-яА-Я])/u', $title)) {
            return false;
        }
//        if(!preg_match('/( )/u', $title)) {
//            return false;
//        }
        $title = ucfirst($title);

        return $title;
    }

}