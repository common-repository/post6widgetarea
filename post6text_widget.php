<?php

/**
 * Description of post6text_widget
 *
 * Author: enomoto@celtislab
 * Version: 0.6.0
 * Author URI: http://celtislab.net/
 */


class post6text_widget extends WP_Widget {

    const   DOMAIN = 'Post6WidgetArea'; //翻訳用ドメイン定義
    
    //コンストラクタ　ウイジェット情報の登録
    function __construct() {
        $name = 'Post6 '.__('Text');
        $widget_ops = array('classname' => 'post6text_widget', 'description' => __('Any text and HTML with lifetime and category judgement function', self::DOMAIN));
        //任意のテキストとHTML（有効期間とカテゴリ判定機能付き）
        $control_ops = array('width' => 400, 'height' => 350);
        parent::__construct( false, $name, $widget_ops, $control_ops );
    }
 
    //ウイジェットのコンテンツ出力(echo)
    public function widget($args, $instance) {
        global $post6widgetarea;    //Post6widgetarea クラスインスタンス
        
        //連想配列 $args を変数名($before_widget, $before_title, $after_title, $after_widget, ...)に展開
		extract($args);
        $title = $instance['title'];
        $hide  = $instance['hidetitle'];
        $filter= $instance['filter'];
		//$movs  = $instance['movescript'];
        $cmtext= $instance['cmtext'];
        $sbtext= $instance['sbtext'];
        $sdate = CeltisLib::get_check_date($instance['sdate']);
        $edate = CeltisLib::get_check_date($instance['edate']);

        //有効期間
        $datefg = TRUE;
        $now = strtotime("now");
        if (isset($sdate)){
            $datefg = (strtotime($sdate) <= $now )? TRUE : FALSE;
        }
        if($datefg){
            if (isset($edate)){
                $datefg = (strtotime($edate) >= $now )? TRUE : FALSE;
            }
        }
        $idfg = FALSE;
        if($instance['in_ex_type'] == 'include'){
            $ispage = CeltisLib::is_include_page($instance['sel_pid']);
            $issingle = CeltisLib::is_include_single($instance['sel_cat'], $instance['sel_pid']);
            if($ispage || $issingle)  //カテゴリーかポストIDに含まれているか？
                $idfg = TRUE;
        }
        else {
            $ispage = CeltisLib::isnot_exclude_page($instance['sel_pid']);
            $issingle = CeltisLib::isnot_exclude_single($instance['sel_cat'], $instance['sel_pid']);
            if($ispage && $issingle)  //カテゴリーとポストIDともに除外されているか？
                $idfg = TRUE;
        }
        
		echo $before_widget;
		if ( !empty( $title ) && empty( $hide )) {
            echo $before_title . $title . $after_title;
        }

?>
    <div class="textwidget">
<?php
        //有効期間内のカテゴリー、ポストID比較条件に一致しているか？
        if($datefg && $idfg)
            $outtext = $cmtext;
        else 
            $outtext = $sbtext;
        echo !empty( $filter ) ? wpautop( $outtext ) : $outtext;
?>
    </div>
<?php

		echo $after_widget;
    }

    //アップデート　設定値確認と保存処理
    // OK: Return　新しいインスタンス（インスタンスは自動的に保存更新）
    // NG: Return  false　          （インスタンスは保存/更新されません）
    public function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['hidetitle'] = isset($new_instance['hidetitle']);
        //有効期間
        $instance['sdate'] = CeltisLib::get_check_date($new_instance['sdate']);
        $instance['edate'] = CeltisLib::get_check_date($new_instance['edate']);
        //対象カテゴリー
        $instance['in_ex_type'] = strip_tags($new_instance['in_ex_type']);
        $instance['sel_cat'] = array();
        if(isset($_POST['post_category'])){
            $cat = array();
            foreach((array) $_POST['post_category'] AS $val){
                if(!empty($val) && is_numeric($val))
                    $cat[] = (int)$val;
            }
            $instance['sel_cat'] = $cat;
        }
        //対象ポストID
		$instance['sel_pid'] = strip_tags($new_instance['sel_pid']);
        if(isset($instance['sel_pid'])){
            $idlist = array();
            $ids = explode(",", $instance['sel_pid']);
            for($x = 0; $x<count($ids); $x++) {
                $id = (int)trim($ids[$x]);
                if($id>0)
                    $idlist[] = $id;
            }
            $instance['sel_pid'] = $idlist;
        }
        //テキスト
        if ( current_user_can('unfiltered_html') ){
			$instance['cmtext'] =  $new_instance['cmtext'];
			$instance['sbtext'] =  $new_instance['sbtext'];
        }
		else {
			$instance['cmtext'] = stripslashes( wp_filter_post_kses( addslashes($new_instance['cmtext']) ) ); // wp_filter_post_kses() expects slashed
			$instance['sbtext'] = stripslashes( wp_filter_post_kses( addslashes($new_instance['sbtext']) ) );
        }
        //チェックボックス
        $instance['filter'] = isset($new_instance['filter']);
        
        return $instance;        
    }

    
    //設定値の入力フォーム
    public function form($instance) {
        //値がない項目に対して引数で指定した初期値を設定
        $default  = array( 'title' => '', 'cmtext' => '', 'sbtext' => '', 'sdate' => NULL, 'edate' => NULL, 'in_ex_type' => 'exclude', 'sel_cat' => array(), 'sel_pid' => array(), 'filter'=> FALSE, 'hidetitle'=> FALSE);
		$instance = wp_parse_args( (array) $instance,  $default);
        //タイトルデータ
        $ttl    = array('id'  => $this->get_field_id('title'),
                        'name'=> $this->get_field_name('title'),
                        'val' => strip_tags($instance['title']) );
        //条件一致テキストデータ condition match
        $cmt    = array('id'  => $this->get_field_id('cmtext'),
                        'name'=> $this->get_field_name('cmtext'),
                        'val' => esc_textarea($instance['cmtext']) );
        //代替テキストデータ  substitute
        $sbt    = array('id'  => $this->get_field_id('sbtext'),
                        'name'=> $this->get_field_name('sbtext'),
                        'val' => esc_textarea($instance['sbtext']) );
        //有効期間
        $sdate  = array('id'  => $this->get_field_id('sdate'),
                        'name'=> $this->get_field_name('sdate'),
                        'val' => CeltisLib::get_check_date($instance['sdate']) );
        $edate  = array('id'  => $this->get_field_id('edate'),
                        'name'=> $this->get_field_name('edate'),
                        'val' => CeltisLib::get_check_date($instance['edate']) );
        //ラジオボタン default exclude
        $i_e_type = array('id'  => $this->get_field_id('in_ex_type'),
                          'name'=> $this->get_field_name('in_ex_type'),
                          'val' => $instance['in_ex_type'] );
        //カテゴリー一致
        $sel_cat = array('id'  => $this->get_field_id('sel_cat'),
                         'name'=> $this->get_field_name('sel_cat'),
                         'val' => $instance['sel_cat'] );
        //ポストID一致
        $sel_pid = array('id'  => $this->get_field_id('sel_pid'),
                         'name'=> $this->get_field_name('sel_pid'),
                         'val' => $instance['sel_pid'] );
        //チェックボックスデータ
        $hid    = array('id'  => $this->get_field_id('hidetitle'),
                        'name'=> $this->get_field_name('hidetitle'),
                        'val' => $instance['hidetitle'] );
        $flt    = array('id'  => $this->get_field_id('filter'),
                        'name'=> $this->get_field_name('filter'),
                        'val' => $instance['filter'] );

?>
		<p>
            <label for="<?php echo $ttl['id']; ?>"><strong><?php _e('Title:'); ?></strong></label>&nbsp;
            <input id="<?php echo $hid['id']; ?>" name="<?php echo $hid['name']; ?>" type="checkbox" <?php checked(isset($hid['val']) ? $hid['val'] : 0); ?> />&nbsp;
            <label for="<?php echo $hid['id']; ?>"><?php _e('Hide'); ?></label>
            <input class="widefat" id="<?php echo $ttl['id']; ?>" name="<?php echo $ttl['name']; ?>" type="text" value="<?php echo esc_attr($ttl['val']); ?>" />
        </p>
		<p><strong><?php _e('Condition setting', self::DOMAIN ); ?></strong></p>
        <div class="post6wgoption">
            <p>
            <?php _e('Lifetime', self::DOMAIN ); ?>&nbsp;
            <input id="<?php echo $sdate['id']; ?>" name="<?php echo $sdate['name']; ?>" type="date" value="<?php echo $sdate['val']; ?>" title="Start date:YYYY-MM-DD (Blank:Not specified)" />
            <?php _e(' - ' ); ?>
            <input id="<?php echo $edate['id']; ?>" name="<?php echo $edate['name']; ?>" type="date" value="<?php echo $edate['val']; ?>" title="End date:YYYY-MM-DD (Blank:Not specified)" />
            </p>
            <div class="post6matches">
                <p>
                <?php _e( 'Condition type', self::DOMAIN ) ?>&nbsp;
                <label><input type="radio" id="<?php echo $i_e_type['id']; ?>" name="<?php echo $i_e_type['name'] ?>" value="exclude" <?php checked('exclude', $i_e_type['val']); ?>/>
                <?php _e('Exclude', self::DOMAIN  ); ?></label>
                <label><input type="radio" id="<?php echo $i_e_type['id']; ?>" name="<?php echo $i_e_type['name'] ?>" value="include" <?php checked('include', $i_e_type['val']); ?>/>
                <?php _e('Include', self::DOMAIN  ); ?></label>
                </p>
                <?php _e('Category select', self::DOMAIN ); ?>
                <div class="post6categorydiv">
                    <ul class="categorychecklist"  id="<?php echo $sel_cat['id']; ?>" name="<?php echo $sel_cat['name']; ?>"  >
                        <?php wp_category_checklist(0,0,$sel_cat['val'],FALSE,NULL,FALSE); ?>
                    </ul>
                </div>
                
                <p>
                    <label><?php _e('Post ID select', self::DOMAIN ); ?>&nbsp;
                    <input id="<?php echo $sel_pid['id']; ?>" name="<?php echo $sel_pid['name']; ?>" type="text" style="width:250px;"  value="<?php echo implode(",", $sel_pid['val']); ?>" /></label>
                </p>
            </div>    
        </div>

		<p><strong><?php _e('Condition matches Text', self::DOMAIN ); ?></strong></p>
        <div class="post6wgoption">
            <textarea class="widefat" rows="8" cols="20" id="<?php echo $cmt['id']; ?>" name="<?php echo $cmt['name']; ?>"><?php echo $cmt['val']; ?></textarea>
        </div>    		 
        
		<p><strong><?php _e('Substitute Text', self::DOMAIN ); ?></strong></p>
        <div class="post6wgoption">
            <textarea class="widefat" rows="8" cols="20" id="<?php echo $sbt['id']; ?>" name="<?php echo $sbt['name']; ?>"><?php echo $sbt['val']; ?></textarea>
        </div>    		 
		<p>
            <input id="<?php echo $flt['id']; ?>" name="<?php echo $flt['name']; ?>" type="checkbox" <?php checked(isset($flt['val']) ? $flt['val'] : 0); ?> />&nbsp;
            <label for="<?php echo $flt['id']; ?>"><?php _e('Automatically add paragraphs'); ?></label>
        </p>
<?php

    }
    
}

?>
