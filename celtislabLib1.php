<?php

/**
 * Description of celtislabLib1
 *
 * Author: enomoto@celtislab
 * Version: 0.5.1
 * Author URI: http://celtislab.net/
 */

class CeltisLib {

    //除外指定ポストIDの固定ページであるか判定する
    public static function isnot_exclude_page($exclude_id)
    {
        $exclude = (count($exclude_id) > 0 ) && is_page($exclude_id);
        return( ! $exclude );
    }
   
    //指定ポストIDの固定ページであるか判定する
    public static function is_include_page($include_id)
    {
        $include = (count($include_id) > 0 ) && is_page($include_id);
        return( $include );
    }

    //除外カテゴリーまたは除外指定ポストIDの投稿記事であるか判定する
    public static function isnot_exclude_single($exclude_cat, $exclude_id)
    {
        $exclude1 = in_category($exclude_cat);
        $exclude2 = (count($exclude_id) > 0 ) && is_single($exclude_id);
        return( (! $exclude1) && (! $exclude2) );
    }
    
    //指定カテゴリーまたは指定ポストIDの投稿記事であるか判定する
    public static function is_include_single($include_cat, $include_id)
    {
        $include1 = (count($include_cat) > 0 ) && in_category($include_cat);
        $include2 = (count($include_id) > 0 ) && is_single($include_id);
        return( $include1 || $include2 );
    }

    //dynamic_sidebar 内からの実行であるかバックトレースから判定する
    public static function in_dynamic_sidebar()
    {
        $in_flag = false;
        $trace = debug_backtrace();
        foreach ($trace as $stp) {
            if(isset($stp['function'])){
                if($stp['function'] === "dynamic_sidebar"){
                    $in_flag = true;
                    break;
                }
            }
        }
        return $in_flag;
    }

    //dyndamic_sidebar の文字列化
    public static function get_mydynamic_sidebar($index = 1)
    {
        ob_start();
        dynamic_sidebar($index);
        $sidebar_contents = ob_get_clean();
        return $sidebar_contents;
    }
 
    
    //短縮URL取得　（Tweet ボタンでの使用を想定）
    //エラーならパーマリンクを戻す
    public static function get_shorturl($permalink, $type = 1) 
    {
        $url = $permalink;
        if($type == 1){ //tinyurl
            $maketiny = 'http://tinyurl.com/api-create.php?url='.$url;

            $ch = curl_init();
            $timeout = 5;
            curl_setopt($ch, CURLOPT_URL, $maketiny);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

            $tinyurl = curl_exec($ch);
            if(! curl_errno($ch)){
                $url = $tinyurl;
            }
            curl_close($ch);
        }
        return $url;
    }
   
    //日付 YYYY-MM-DD の有効性チェックと取得
    // 未設定/無効な日付なら NULL をリターンする
    // セパレータが / や . の場合は YYYY-MM-DD 形式にフォーマットし直す
    public static function get_check_date($date)
    {
        if (isset($date)) {
            $dateval = strtotime( trim($date) );
            if($dateval !== FALSE){
                if( checkdate(date('m', $dateval), date('d', $dateval), date('Y', $dateval)) !== FALSE ){
                    $date = date('Y-m-d', $dateval);
                    return $date;
                }
            }
        }
        return NULL;
    }  
 

    //HTML の指定タグ位置を取得（大文字、小文字を区別しない）
    // $html 検索するHTML文
    // $stag スタートタグ 例 <script
    // $etag エンドタグ   例 /script>
    // $ofset検索開始位置 0- (strlen()-1)
    // 戻り値 位置情報配列(start, end)  エラー時は FALSE
    public static function htmltagpos($html, $stag, $etag, $ofset=0)
    {
        $pos = FALSE;
        $start = stripos($html, $stag, $ofset);
        if($start !== FALSE){
            $end = stripos($html, $etag, $start);
            if($end !== FALSE){
                $end += (strlen($etag)-1);
                $pos = array($start, $end);
            }
        }
        return $pos;
    }
    
    //HTML の指定タグを分割して取り出す
    // $html 検索するHTML文
    // $stag スタートタグ 例 <script
    // $etag エンドタグ   例 /script>
    // $ofset検索開始位置 0- (strlen()-1)
    // 戻り値 分割 HTML文配列 $newhtml(指定タグ以外の部分, 指定タグ1, 指定タグ2, --- 指定タグN)  
    public static function htmltagsplit($html, $stag, $etag, $ofset=0)
    {
        $newhtml[0] = '';
        if(strlen($html) > 0){
            $start = $ofset;
            $end = strlen($html) - $ofset - 1;
            if($start != 0)
                $newhtml[0] = substr($html, 0, $start);
            for($cnt=0; ($pos = CeltisLib::htmltagpos($html, $stag, $etag, $start)) !== FALSE; $cnt++){
                $newhtml[0] = $newhtml[0] . substr($html, $start, $pos[0] - $start);
                $len = $pos[1] - $pos[0] + 1;
                $newhtml[$cnt+1] = substr($html, $pos[0], $len);
                $start = $pos[1] + 1;
            }
            if($start < $end)
                $newhtml[0] = $newhtml[0] . substr($html, $start, $end - $start + 1);
        }
        return $newhtml;
    }
    
    //script タグ分割取り出し専用（但し、外部 JavaScript ファイルをロードするもののみ）
    public static function htmlscriptjsfilesplit($html, $ofset=0)
    {
        $newhtml[0] = '';
        if(strlen($html) > 0){
            $start = $ofset;
            $end = strlen($html) - $ofset - 1;
            if($start != 0)
                $newhtml[0] = substr($html, 0, $start);
            for($cnt=0; ($pos = CeltisLib::htmltagpos($html, '<script', '/script>', $start)) !== FALSE; ){
                $len = $pos[1] - $pos[0] + 1;
                $sephtml = substr($html, $pos[0], $len);
                //src= '/hoge/hoge.js' のような外部 JavaScript ファイルのロードが有るかチェック
                if(CeltisLib::htmltagpos($sephtml, 'src', '.js') !== FALSE){
                    $newhtml[0] = $newhtml[0] . substr($html, $start, $pos[0] - $start);
                    $newhtml[$cnt+1] = $sephtml;
                    $cnt++;
                }
                else {
                    $newhtml[0] = $newhtml[0] . substr($html, $start, $pos[0] - $start) . $sephtml;
                }
                $start = $pos[1] + 1;
            }
            if($start < $end)
                $newhtml[0] = $newhtml[0] . substr($html, $start, $end - $start + 1);
        }
        return $newhtml;
    }
}

?>
