<?php

/*
  Plugin Name: Post 6 WidgetArea
  Plugin URI: http://celtislab.net/wp_plugin_post6widgetarea
  Description: Add 6 widget areas before and after the post article, etc. 1.wp_head 2.Start position of the page  3.Before the single post content  4.Articles in short code or more tag position  5.After the single post content  6.End position of the page
  Author: enomoto@celtislab
  Version: 0.6.2
  Author URI: http://celtislab.net/
  License: GPLv2
  Text Domain: Post6WidgetArea
  Domain Path: /languages
*/

require_once 'celtislabLib1.php';
require_once 'post6text_widget.php';

$post6widgetarea = new Post6widgetarea();

class Post6widgetarea {

    const   DOMAIN = 'Post6WidgetArea'; //翻訳用ドメイン定義

    private $goption = array();
    private $scnum = 0;
  
    //コンストラクタ
    public function __construct() {

        load_plugin_textdomain(self::DOMAIN, false, basename( dirname( __FILE__ ) ).'/languages' );

        $default   = array( 'home' => false, 'page' => false, 'single' => true, 'archive' => true, 'exclude_id'=> array(), 'exclude_cat' => array(), 'sidebar' => false, 'shortcode' =>'Post6ins', 'more' => false );
        //delete_option('post6widget_option');  //for debug
		$instance  = get_option('post6widget_option');
		$getoption = wp_parse_args( (array) $instance,  $default);

		if($getoption && is_array($getoption)) {
			foreach($getoption AS $k=>$v) {
                $this->goption[$k] = $v;
			}
		}         
        $this->update_exclude_option();        // update post
        
        //ウィジェットエリア定義
        add_action('widgets_init', array(&$this, 'my_register_wedgets'));

        if(is_admin()) {
            //管理画面（設定メニュー）
            add_action('admin_menu', array(&$this, 'my_option_menu'));        //オプション表示
            add_action('admin_init', array(&$this, 'my_option_register'));    //オプション更新

            //プラグイン削除時のフック(コールバック関数を static にする必要あり）
            if ( function_exists('register_uninstall_hook') )
                register_uninstall_hook(__FILE__, 'Post6widgetarea::my_uninstall_hook');
        }
        else {
            //ウィジェットをアクション／フィルターフックにセット
            add_action('wp_head',    array(&$this, 'my_wp_head'));
            add_action('loop_start', array(&$this, 'my_loop_start'));
            add_filter('the_content',array(&$this, 'my_content'));
            add_action('loop_end',   array(&$this, 'my_loop_end'));

            add_shortcode($this->goption['shortcode'],  array(&$this, 'my_content_shortcode'));
            $this->scnum = 0;

       }

    }

    //-------------------------------------------------------------------------
    //設定オプション表示（メニュー）
    public function my_option_menu()
    {
        $page = add_options_page( 'Post 6 WidgetArea Settings', __('Post 6 WidgetArea', self::DOMAIN), 'manage_options', __FILE__, array(&$this,'post6widget_area_options'));
    }

    //設定オプション更新  
    //  引数１：グループ名(settings_fields関数の引数で使用する)
    //  引数２：オプション名(input要素などのname属性で使用する）
    public function my_option_register()
    {
        register_setting('post6widget_optiongroup', 'post6widget_option');
        //管理画面の<head> 内でCSSファイルを読みこませる
        $urlpath = plugins_url('Post6style.css', __FILE__);
        wp_register_style('post6style', $urlpath);
        wp_enqueue_style('post6style');
    }

    //プラグイン削除時のオプションクリア
    public static function my_uninstall_hook()
    {
        delete_option('post6widget_option');
    }

    //ウイジェットエリア登録
    public function my_register_wedgets()
    {
        register_sidebar( array(
            'name'          => __( 'Post6 wp_head widget', self::DOMAIN),
            'id'            => 'post6_wp_head',
            //'description'   => __( 'wp_head 部で実行するウィジェットエリアです. CSS や script 等の記述に利用します' ),
            'description'   => __( 'Widget area that runs in the wp_head hook.', self::DOMAIN ),
            'before_title'  => '<div class="widget-title">',
            'after_title'   => '</div>',
//            'before_widget' => '<div id="%1$s" class="widget-wrapper %2$s">',
//            'after_widget'  => '</div>'
            'before_widget' => '',
            'after_widget'  => ''
        ) );   
        register_sidebar( array(
            'name'          => __( 'Post6 loop_start widget', self::DOMAIN),
            'id'            => 'post6_loop_start',
            //'description'   => __( 'ホーム/固定/個別記事/アーカイブページエリアのループスタート部で実行するウィジェットエリアです' ),
            'description'   => __( 'Widget area that runs in a loop_start hook of the home/page/single/archive page.', self::DOMAIN ),
            'before_title'  => '<div class="widget-title">',
            'after_title'   => '</div>',
            'before_widget' => '<div id="%1$s" class="widget-wrapper %2$s">',
            'after_widget'  => '</div>'
         ) );
        register_sidebar( array(
            'name'          => __( 'Post6 before content widget', self::DOMAIN ),
            'id'            => 'post6_before_content',
            //'description'   => __( '個別記事のコンテンツ前で実行するウィジェットエリアです' ),
            'description'   => __( 'Widget area that runs in the_content hook before the content of a single post.', self::DOMAIN ),
            'before_title'  => '<div class="widget-title">',
            'after_title'   => '</div>',
            'before_widget' => '<div id="%1$s" class="widget-wrapper %2$s">',
            'after_widget'  => '</div>'
         ) );
        register_sidebar( array(
            'name'          => __( 'Post6 shortcode widget', self::DOMAIN ),
            'id'            => 'post6_shortcode',
            //'description'   => __( '個別記事コンテンツのショートコード位置で実行するウィジェットエリアです' ),
            'description'   => __( 'Widget area that runs at Single Post article shortcode or more tag.', self::DOMAIN ),
            'before_title'  => '<div class="widget-title">',
            'after_title'   => '</div>',
            'before_widget' => '<div id="%1$s" class="widget-wrapper %2$s">',
            'after_widget'  => '</div>'
         ) );
        register_sidebar( array(
            'name'          => __( 'Post6 after content widget', self::DOMAIN ),
            'id'            => 'post6_after_content',
            //'description'   => __( '個別記事のコンテンツ後で実行するウィジェットエリアです' ),
            'description'   => __( 'Widget area that runs in the_content hook after the content of a single post.', self::DOMAIN ),
            'before_title'  => '<div class="widget-title">',
            'after_title'   => '</div>',
            'before_widget' => '<div id="%1$s" class="widget-wrapper %2$s">',
            'after_widget'  => '</div>'
        ) );   
        register_sidebar( array(
            'name'          => __( 'Post6 loop_end widget', self::DOMAIN ),
            'id'            => 'post6_loop_end',
            //'description'   => __( 'ホーム/固定/投稿記事/アーカイブページエリアのループスエンド部で実行するウィジェットエリアです' ),
            'description'   => __( 'Widget area that runs in a loop_end hook of the home/page/single/archive page.', self::DOMAIN ),
            'before_title'  => '<div class="widget-title">',
            'after_title'   => '</div>',
            'before_widget' => '<div id="%1$s" class="widget-wrapper %2$s">',
            'after_widget'  => '</div>'
        ) );  
        
        register_widget("post6text_widget");
    }

    //パラメータ更新データの受信 exclude_id, exclude_cat は表示前に型変換しているので
    //options.php に渡す前にデータを検証してから型を戻してセットし直す
    //
    public function update_exclude_option()
    {
        if (!empty($_POST['action'])) {
            if($_POST['action'] == "update"){
                foreach($this->goption as $k=>$v) {
                    if($k=='home' || $k=='page' || $k=='single' || $k=='archive' || $k=='sidebar' || $k=='more') {
                        //チェックボックス値が　false/0/empty だと POST に現れないので値をセットして保存されるようにする 
                        if(! isset($_POST['post6widget_option'][$k])) 
                            $_POST['post6widget_option'][$k] = 0;

                        $this->goption[$k] = (bool) $_POST['post6widget_option'][$k];
                    }
                }
                if(isset($_POST['post6widget_option']['exclude_id'])){
                    $idlist = array();
                    $ids = explode(",", $_POST['post6widget_option']['exclude_id']);
                    for($x = 0; $x<count($ids); $x++) {
                        $id = (int)trim($ids[$x]);
                        if($id>0)
                            $idlist[] = $id;
                    }
                    $this->goption["exclude_id"] = $idlist;
                    $_POST['post6widget_option']['exclude_id'] = $idlist;
                }
                if(isset($_POST['post_category'])){
                    $excat = array();
                    foreach((array) $_POST['post_category'] AS $exval){
                        if(!empty($exval) && is_numeric($exval))
                            $excat[] = (int)$exval;
                    }
                    $this->goption["exclude_cat"] = $excat;
                    $_POST['post6widget_option']['exclude_cat'] = $excat;
                }
            }
        }
    }

    public function my_wp_head()
    {
        //head に div タグを定義したら他のプラグインのJSでエラー発生 (Uncaught Error: NOT_FOUND_ERR: DOM Exception 8 )
        //dynamic_sidebar('post6_wp_head');
        $new_content = CeltisLib::get_mydynamic_sidebar('post6_wp_head');
        $new_content = preg_replace('/<div class="widget-title".*?<\/div>/ismu', '', $new_content); //divタグ(Title)を取り除く
        $new_content = preg_replace('/<\/?div.*?>/ismu', '', $new_content); //divタグを取り除く
        $new_content = preg_replace('/<\/?p.*?>/ismu', '', $new_content);   //pタグを取り除く
        echo trim($new_content) . PHP_EOL;;
        remove_action('wp_head', array(&$this,'my_wp_head'));
    }

    public function my_loop_start()
    {
        $option = $this->goption;
        if((! $option['sidebar']) || (! CeltisLib::in_dynamic_sidebar())){
            if(  (is_home()     && $option['home'])
              || (is_page()     && $option['page']   && CeltisLib::isnot_exclude_page($option["exclude_id"]) )
              || (is_single()   && $option['single'] && CeltisLib::isnot_exclude_single($option["exclude_cat"], $option["exclude_id"]) )
              || (is_archive()  && $option['archive']) ) {               
        		?><div id="post6widget-loop_start" class="post6widget-area" ><?php
                dynamic_sidebar('post6_loop_start');
        		?></div><?php
            }
        }
        remove_action('loop_start', array(&$this,'my_loop_start'));
    }

    public function my_loop_end()
    {
        $option = $this->goption;
        if((! $option['sidebar']) || (! CeltisLib::in_dynamic_sidebar())){
            if(  (is_home()     && $option['home'])
              || (is_page()     && $option['page']   && CeltisLib::isnot_exclude_page($option["exclude_id"])  )
              || (is_single()   && $option['single'] && CeltisLib::isnot_exclude_single($option["exclude_cat"], $option["exclude_id"]) )
              || (is_archive()  && $option['archive']) ) {
        		?><div id="post6widget-loop_end" class="post6widget-area" ><?php
                dynamic_sidebar('post6_loop_end');
        		?></div><?php
            }
        }
        remove_action('loop_end', array(&$this, 'my_loop_end'));
    }

    public function my_content_shortcode()
    {
        $option = $this->goption;
        $ins_content = '';
        if(! is_single()){
            return $ins_content;
        }
        else {
            if((! $option['sidebar']) || (! CeltisLib::in_dynamic_sidebar())){
                if(CeltisLib::isnot_exclude_single($option["exclude_cat"], $option["exclude_id"]) ){
                    if($this->scnum === 0){
                        $ins_content .= '<div id="post6widget-shortcode" class="post6widget-area" >';
                        $ins_content .= CeltisLib::get_mydynamic_sidebar('post6_shortcode');
                        $ins_content .= '</div>';
                        $this->scnum++;
                    }
                }
            }
            return $ins_content;
        }
    }

    public function my_content($content)
    {
        $option = $this->goption;
        if(! is_single()){
            return $content;
        }
        else {
            $new_content = "";
            if((! $option['sidebar']) || (! CeltisLib::in_dynamic_sidebar())){
                if(CeltisLib::isnot_exclude_single($option["exclude_cat"], $option["exclude_id"]) ){
            		$new_content .= '<div id="post6widget-before_content" class="post6widget-area" >';
                    $new_content .= CeltisLib::get_mydynamic_sidebar('post6_before_content');
                    $new_content .= '</div>';
                    
                    if($option['more']){
                        $pattern = '/(<span id=\"more\-[0-9]+?\"><\/span>)/smu';
                        $content = preg_replace($pattern, "[".$option['shortcode']."]", $content);
                    }
                    $new_content .= $content;
                    
            		$new_content .= '<div id="post6widget-after_content" class="post6widget-area" >';
                    $new_content .= CeltisLib::get_mydynamic_sidebar('post6_after_content');
                    $new_content .= '</div>';
                    
                }
                else
                    $new_content .= $content;
            }
            return $new_content;
        }
    }

    //-------------------------------------------------------------------------
    public function post6widget_area_options() {
       ?>
      <div class="post6wrap">
        <div id="icon-options-general" class="icon32"><br /></div>
        <h2><?php _e( 'Post 6 WidgetArea Settings', self::DOMAIN ); ?></h2>
        <p><?php _e( 'Post 6 WidgetArea plugin adds a widget area of 6 locations around blog posts, etc.', self::DOMAIN ); // Post 6 WidgetArea プラグインは、ブログ記事前後等に６箇所のウイジェットエリアを追加します ?></p>
        <p><?php _e( ' 1.wp_head (In the HTML &lt;head&gt; element)', self::DOMAIN ); //１．ヘッダー、２．ぺージエリアスタート部、３．投稿記事タイトル後、４．記事中のショートコード又はモアタグ位置、５．投稿記事コンテンツ後、６．ページエリアエンド部 ?></p>
        <p><?php _e( ' 2.Start position of the page area', self::DOMAIN ); ?></p>
        <p><?php _e( ' 3.Before the single post content', self::DOMAIN );  ?></p>
        <p><?php _e( ' 4.Articles in short code or more tag position', self::DOMAIN ); ?></p>
        <p><?php _e( ' 5.After the single post content', self::DOMAIN ); ?></p>
        <p><?php _e( ' 6.End position of the page area', self::DOMAIN ); ?></p>
        <p><?php _e( 'Widget area is added to the management screen Appearance → Widgets. By using the [text widget] or [Post6 text widget], you will be able to set up boilerplate and advertising, and social buttons.', self::DOMAIN ); //ウイジェットエリアは、管理画面の外観→ウイジェットへ追加されているので、定型文や広告、ソーシャルボタン設置等のテキストやHTMLコードを「テキストウイジェット」を用いて挿入して下さい ?></p>
        <p><?php _e( 'Introduced the [PHP Code Widget] plug-in separately, in order to run the PHP code, please use the [PHP Code widget]', self::DOMAIN ); //PHPコードを実行させる場合には、別途「Executable PHP widget」プラグインを導入して、「PHP Code ウイジェット」を使用して下さい ?></p>
        <p><?php _e( 'With [Post6 wp_head widget], you can also insert information such as metadata and CSS into the head element of the HTML', self::DOMAIN ); //また、HTMLのhead要素内へのウイジェットエリアには、メタデータやCSS等のドキュメントについての情報を挿入することが出来ます ?></p>
        <p><br /></p>
        <!-- submit ボタンクリックで　input 要素の name 属性データを post メソッドで option.php へ送信する -->
        <form method="post" action="options.php">
        <?php settings_fields('post6widget_optiongroup'); ?>
        <?php 
            $option = $this->goption;
         ?>
        <h3><?php _e( 'Type pages that enable the processing of the [Post6 loop_start widget / Post6 loop_end widget]', self::DOMAIN ); //記事エリアスタート/エンド部のウイジェット処理を有効とするページ種別 ?></h3>
        <p><?php _e( 'Please select the type of page you want to enable processing widget', self::DOMAIN ); //ウイジェット処理を有効にするページ種別を指定して下さい ?></p>
        <label><input type="checkbox" name="post6widget_option[home]" value="1" <?php checked( $option['home'], 1 ); ?>" /><?php _e( ' home', self::DOMAIN ); // ホームページ ?></label><br />
        <label><input type="checkbox" name="post6widget_option[page]" value="1" <?php checked( $option['page'], 1 ); ?>" /><?php _e( ' page', self::DOMAIN ); // 固定ページ ?></label><br />
        <label><input type="checkbox" name="post6widget_option[single]" value="1" <?php checked( $option['single'], 1 ); ?>" /><?php _e( ' single', self::DOMAIN ); // 個別投稿ページ ?></label><br />
        <label><input type="checkbox" name="post6widget_option[archive]" value="1" <?php checked( $option['archive'], 1 ); ?>" /><?php _e( ' archive', self::DOMAIN ); // アーカイブページ ?></label><br />
        <p><br /><?php _e( '[Post6 before content widget / Post6 shortcode widget / Post6 before content widget] is valid only on single post pages', self::DOMAIN ); //※記事タイトル後、ショートコードによる挿入、記事コンテンツ後のウイジェット処理は、個別投稿ページのみが対象となります ?></p>
        <p><br /></p>
        <h3><?php _e( 'Exclusion condition setting processing is not performed widget', self::DOMAIN ); //ウイジェット処理を行わない除外条件設定 ?></h3>
        <p><?php _e( 'Exclude articles specified category', self::DOMAIN ); //指定カテゴリーの投稿記事を除外 ?></p>
            <div class="post6categorydiv">
                <ul class="categorychecklist" >
                    <?php wp_category_checklist(0,0,$option['exclude_cat'],FALSE,NULL,FALSE); ?>
                </ul>
            </div>
        <p><?php _e( 'Exclusion posts post ID to the specified fixed page (Must be specified in the comma-separated values)', self::DOMAIN ); //指定 post ID の投稿記事、固定ページを除外 (カンマ区切りで指定して下さい) ?></p>
        <p><label>Post ID <input type="text" style="width:200px;" name="post6widget_option[exclude_id]" value="<?php echo implode(",", $option['exclude_id']); ?>" /></label></p>
        <p><br /></p>
        <p><?php _e( 'Please specify whether you want to exclude output destination of execution if the widgets in the sidebar', self::DOMAIN ); //実行処理の出力先がサイドバーウイジェット内なら除外するかを指定して下さい ?></p>
        <label><input type="checkbox" name="post6widget_option[sidebar]" value="1" <?php checked( $option['sidebar'], 1 ); ?>" /><?php _e( 'Excluded in the sidebar widget', self::DOMAIN ); // サイドバーウイジェット内は除外する ?></label>
        <p><br /></p>
        <h3><?php _e( 'Widget area inserted into the article in', self::DOMAIN ); //記事中へウイジェットエリア挿入 ?></h3>
        <p><?php _e( 'Widget area inserted into the position of the short code string that is defined', self::DOMAIN ); //定義されているショートコード文字列の位置へウィジェットエリア挿入 ?></p>
        <p><label><?php _e( 'Shortcode name ', self::DOMAIN ); //ショートコード名称： ?><input type="text" name="post6widget_option[shortcode]" value="<?php echo $option['shortcode']; ?>" /></label></p>
        <p><label><input type="checkbox" name="post6widget_option[more]" value="1" <?php checked( $option['more'], 1 ); ?>" /><?php _e( 'If there is &lt;more&gt; tag definition, insert there the widget area', self::DOMAIN ); ?></label><br /></p>
        
        <p class="submit">
          <input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
        </p>		
        </form>
      </div>
    <?php }
    
}


?>