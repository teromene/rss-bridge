<?php
/**
*
* @name Les 400 Culs 
* @description La plan�te sexe vue par Agn�s Girard via rss-bridge
* @update 20/02/2014
*/
define("SEXE", "http://sexes.blogs.liberation.fr");
class Les400Culs extends BridgeAbstract{

    public function collectData(array $param){
        $html = file_get_html($this->getURI()) or $this->returnError('Could not request '.$this->getURI(), 404);

        foreach($html->find('#alpha-inner') as $articles) {
            foreach($articles->find('div.entry') as $article) {
                $header = $article->find('h3.entry-header a', 0);
                $content = $article->find('div.entry-content', 0);


                $item = new Item();
                $item->title = trim($header->innertext);
                $item->uri = $header->href;
                $item->name = "Agnès Girard";
                // date is stored outside this node !
                $dateHeader = $article->prev_sibling();
                // http://stackoverflow.com/a/6239199/15619 (strtotime is typical amercian bullshit)
                $item->timestamp = DateTime::createFromFormat('d/m/Y', $dateHeader->innertext)->getTimestamp();


                $linkForMore = $content->find('p.entry-more-link a',0);
                if($linkForMore==null) {
                    $item->content = $content->innertext;
                } else {
                    $pageAddress = $linkForMore->href;
                    $articlePage = str_get_html($this->get_cached($linkForMore->href));
                    if($articlePage==null) {
                        $item->content = $content->innertext."\n<p>".$linkForMore->outertext."</p>";
                    } else {
                        // TODO use some caching there !
                        $fullContent = $articlePage->find('div.entry-content', 0);
                        $item->content = $fullContent->innertext;
                    }
                }
                $this->items[] = $item;
            }
       }
    }

    public function getName(){
        return 'Les 400 Culs';
    }

    public function getURI(){
        return SEXE;
    }

    public function getCacheDuration(){
        return 7200; // 2h hours
    }
    public function getDescription(){
        return "La planète sexe, vue et racontée par Agnès Giard. Et par rss-bridge";
    }
    
    /**
     * Maintain locally cached versions of pages to download to avoid multiple doiwnloads.
     * A file name is generated by replacing all "/" by "_", and the file is saved below this bridge cache
     * @param url url to cache
     * @return content of file as string
     */
    public function get_cached($url) {
        $simplified_url = str_replace(["http://", "https://", "?", "&"], ["", "", "/", "/"], $url);
        $filename =  __DIR__ . '/../cache/'."pages/".$simplified_url;
        if (substr($filename, -1) == '/') {
            $filename = $filename."index.html";
        }
        if(!file_exists($filename)) {
            error_log("we have no local copy of ".$url." Downloading !");
            $dir = substr($filename, 0, strrpos($filename, '/'));
            if(!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
            $this->download_remote($url, $filename);
        }
        return file_get_contents($filename);
    }

    public function download_remote($url , $save_path) {
        $f = fopen( $save_path , 'w+');
        $handle = fopen($url , "rb");
        while (!feof($handle)) {
            $contents = fread($handle, 8192);
            fwrite($f , $contents);
        }
        fclose($handle);
        fclose($f);
    }

}
