<?php

include 'Parsedown.php';    
include 'ParsedownExtra.php';

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

class LA{
    
    protected $PDE;

    protected $style;

    /* config */
    protected $Title;
    protected $ShortTitle;
    protected $Admin;
    protected $Password;
    protected $DisplayName;
    protected $EMail;
    protected $SpecialNavigation;
    protected $SpecialFooter;
    protected $SpecialFooter2;
    protected $SpecialPinned;
    protected $DefaultGallery;
    protected $SelfAuthPath;
    protected $HereHost;
    protected $HereTitle;
    protected $HereShortTitle;
    protected $HereAlbum;
    protected $HereNavigation;
    protected $HereFooter;
    protected $ExpHost;
    protected $ExpAlbum;
    protected $ExpTitle;
    protected $ExpShortTitle;
    protected $ExpCaution;
    protected $ExpNavigation;
    protected $ExpFooter;
    protected $CommentEnabled;
    protected $MastodonToken;
    protected $MastodonURL;
    protected $MastodonPreferredLang;
    protected $HostURL;
    
    protected $Redirect;
    protected $Translations;
    protected $CustomTranslationContent;
    
    protected $CurrentOffset;
    protected $PostsPerPage;
    protected $HotPostCount;
    
    protected $LoggedIn;
    protected $LoginTokens;
    protected $InHereMode;
    protected $InExperimentalMode;
    protected $LanguageAppendix;
    protected $UseRemoteFont;
    
    protected $Posts;
    protected $Threads; // [ keys: first last displayed count]
    protected $WaybackThreads;
    public $Images;
    protected $Galleries;
    protected $Anchors;
    
    protected $ArchiveHandles;
    protected $Archive;
    public    $WayBack;
    protected $YearBegin;
    protected $YearEnd;
    
    protected $Markers;
    
    protected $ExtraScripts;
    
    protected $APubID;
    protected $APubActor;
    protected $APubPublicKey;
    protected $APubPrivateKey;
    
    protected $TIME_STRING;
    
    public $NULL_POST;
    public $NULL_IMAGE;
    public $NULL_IMAGE_DUMMY;
    public $NULL_Gallery;
    
    protected $DoneReadPosts;
    protected $DoneReadImages;
    protected $DoneReadArchive;
    protected $NeedWritePosts;
    protected $NeedWriteImages;
    protected $NeedWriteArchive;
    
    protected $HereDisplayTitle;
    protected $VisitedHere;
    protected $HereNumber;
    protected $HereMainImage;
    
    public $PageType;
    public $CurrentPostID;
    public $HereID;
    public $ActualPostID;
    public $TagID;
    
    function ReadableTime($id){
        $dt = DateTime::createFromFormat('YmdHis', $id);
        return $dt->format('Y/m/d H:i:s');
    }
    function StandardTime($id){
        $dt = DateTime::createFromFormat('YmdHis', $id);
        return $dt->format('Y-m-d\TH:i:sP');
    }
    function FullURL(){
        return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ?
                "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . 
                $_SERVER['REQUEST_URI'];
    }
    function T($str){
        if(!$this->LanguageAppendix) return $str;
        foreach($this->Translations as $entry){
            if($entry['zh']==$str)
                return $entry[$this->LanguageAppendix];
        }
        return $str;
    }
    function SwitchLanguageAndFont(){
        $this->LanguageAppendix = 'zh';   
        if(isset($_COOKIE['la_language'])){
            $this->LanguageAppendix = $_COOKIE['la_language'];
        }else{
            if(isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])){
                $lang = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
                $lang = substr($lang,0,5);
                if(preg_match("/zh/i",$lang))$this->LanguageAppendix = 'zh';
                else $this->LanguageAppendix = 'en';
            }
        }
        if(isset($_COOKIE['la_font'])){
            $this->UseRemoteFont = ($_COOKIE['la_font']!='local');
        }
    }
    function SwitchWayBackMode(){        
        if(isset($_COOKIE['la_wayback'])){
            $this->WayBack = $_COOKIE['la_wayback'];
        }else $this->WayBack = NULL;
    }
    
    function DisplayRedirectConfig(){
        $s = "";
        if(isset($this->Redirect) && isset($this->Redirect[0])) foreach($this->Redirect as $r){
            if($r['for']=='P'){
                $s.=("P ".$r['format'].":".$r['target'].";".PHP_EOL);
            }else if($r['for']=='S'){
                $s.=("S ".$r['format'].":".$r['domain'].":".$r['target'].";".PHP_EOL);
            }
        }
        return $s;
    }
    
    function DoSiteRedirect(){
        if(isset($this->Redirect) && isset($this->Redirect[0])) foreach($this->Redirect as $r){
            if($r['for']=='S'){
                if(preg_match('/'.$r['format'].'/ui', $_SERVER['HTTP_HOST'])){
                    if($_SERVER['REQUEST_URI']=='/'||$_SERVER['REQUEST_URI']==''){
                        header('Location:https://'.$r['domain'].'/index.php?post='.$r['target']); exit;
                    }else{
                        header('Location:https://'.$r['domain'].$_SERVER['REQUEST_URI']); exit;
                    }
                }
            }
        }
    }
    
    function WriteHTACCESS(){
        $conf = fopen('.htaccess','w');
        fwrite($conf,"RewriteEngine on".PHP_EOL.PHP_EOL);
        if(isset($this->Redirect) && isset($this->Redirect[0])) foreach($this->Redirect as $r){
            if($r['for']=='P'){
                fwrite($conf,"RewriteRule ^".$r['format'].'$ /index.php?post='.$r['target'].' [R=302,L]'.PHP_EOL.PHP_EOL);
            }// do site redirect in php.
        }
        fwrite($conf, 'RewriteCond %{HTTPS} !=on'.PHP_EOL.
                      'RewriteCond %{HTTP_HOST} !=localhost'.PHP_EOL.
                      'RewriteCond %{REQUEST_URI}  !^.*(jpg|png|gif)$'.PHP_EOL.
                      'RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]'.PHP_EOL.PHP_EOL);
        fwrite($conf, 'RewriteCond %{HTTP_HOST} !^www\.'.PHP_EOL.
                      'RewriteCond %{HTTP_HOST} !=localhost'.PHP_EOL.
                      'RewriteRule ^(.*)$ https://www.%{HTTP_HOST}%{REQUEST_URI} [L,R=301]'.PHP_EOL.PHP_EOL);
        fwrite($conf,'<Files ~ "\.md$">'.PHP_EOL.'deny from all'.PHP_EOL.'</Files>'.PHP_EOL);
        fflush($conf);fclose($conf);
    }
    
    function BuildRedirectConfig($conf){
        $this->Redirect=[];
        if(preg_match_all('/P\s+(.*)\:\s*([0-9]{14})\s*;/u',$conf,$ma,PREG_SET_ORDER)){
            foreach($ma as $m){
                $redirect=[]; $redirect['for'] = 'P'; $redirect['format'] = $m[1]; $redirect['target'] = $m[2];
                $this->Redirect[]=$redirect;
            }
        }
        if(preg_match_all('/S\s+(\S+)\s*\:\s*(\S+)\s*\:\s*([0-9]{14})\s*;/u',$conf,$ma,PREG_SET_ORDER)){
            foreach($ma as $m){
                $redirect=[]; $redirect['for'] = 'S'; $redirect['format'] = $m[1]; $redirect['domain'] = $m[2]; $redirect['target'] = $m[3];
                $this->Redirect[]=$redirect;
            }
        }
    }
    
    function WriteTokens(){
        $tf = fopen('la_tokens.php','w');
        fwrite($tf,'<?php header("Location:index.php"); exit; ?>'.PHP_EOL.PHP_EOL);
        if(isset($this->LoginTokens) && sizeof($this->LoginTokens)) {
             foreach($this->LoginTokens as $t){
                fwrite($tf,'- '.$t.PHP_EOL);
            }
        }
        fflush($tf);fclose($tf);
    }
    
    function WriteConfig(){
        if(!isset($this->Title)) $this->Title = $this->T('那么的维基');
        if(!isset($this->ShortTitle)) $this->ShortTitle = $this->T('基');
        if(!isset($this->Admin)) $this->Admin = 'admin';
        if(!isset($this->DisplayName)) $this->DisplayName = $this->T('管理员');
        if(!isset($this->Password)) $this->Password = password_hash('Admin', PASSWORD_DEFAULT).PHP_EOL;
        $conf = fopen('la_config.php','w');
        fwrite($conf,'<?php header("Location:index.php"); exit; ?> '.PHP_EOL.PHP_EOL);
        fwrite($conf,'- Title = '.$this->Title.PHP_EOL);
        fwrite($conf,'- ShortTitle = '.$this->ShortTitle.PHP_EOL);
        fwrite($conf,'- Admin = '.$this->Admin.PHP_EOL);
        fwrite($conf,'- DisplayName = '.$this->DisplayName.PHP_EOL);
        fwrite($conf,'- Password = '.$this->Password.PHP_EOL);
        fwrite($conf,'- EMail = '.$this->EMail.PHP_EOL);
        fwrite($conf,'- SpecialNavigation = '.$this->SpecialNavigation.PHP_EOL);
        fwrite($conf,'- SpecialFooter = '.$this->SpecialFooter.PHP_EOL);
        fwrite($conf,'- SpecialFooter2 = '.$this->SpecialFooter2.PHP_EOL);
        fwrite($conf,'- SpecialPinned = '.$this->SpecialPinned.PHP_EOL);
        fwrite($conf,'- DefaultGallery = '.$this->DefaultGallery.PHP_EOL);
        fwrite($conf,'- SelfAuthPath = '.$this->SelfAuthPath.PHP_EOL);
        fwrite($conf,'- CommentEnabled = '.($this->CommentEnabled?"True":"False").PHP_EOL);
        fwrite($conf,'- HereHost = '.$this->HereHost.PHP_EOL);
        fwrite($conf,'- HereTitle = '.$this->HereTitle.PHP_EOL);
        fwrite($conf,'- HereShortTitle = '.$this->HereShortTitle.PHP_EOL);
        fwrite($conf,'- HereAlbum = '.$this->HereAlbum.PHP_EOL);
        fwrite($conf,'- HereNavigation = '.$this->HereNavigation.PHP_EOL);
        fwrite($conf,'- HereFooter = '.$this->HereFooter.PHP_EOL);
        fwrite($conf,'- ExpHost = '.$this->ExpHost.PHP_EOL);
        fwrite($conf,'- ExpTitle = '.$this->ExpTitle.PHP_EOL);
        fwrite($conf,'- ExpShortTitle = '.$this->ExpShortTitle.PHP_EOL);
        fwrite($conf,'- ExpCaution = '.$this->ExpCaution.PHP_EOL);
        fwrite($conf,'- ExpAlbum = '.$this->ExpAlbum.PHP_EOL);
        fwrite($conf,'- ExpNavigation = '.$this->ExpNavigation.PHP_EOL);
        fwrite($conf,'- ExpFooter = '.$this->ExpFooter.PHP_EOL);
        fwrite($conf,'- MastodonToken = '.$this->MastodonToken.PHP_EOL);
        fwrite($conf,'- MastodonURL = '.$this->MastodonURL.PHP_EOL);
        fwrite($conf,'- MastodonPreferredLang = '.$this->MastodonPreferredLang.PHP_EOL);
        fwrite($conf,'- HostURL = '.$this->HostURL.PHP_EOL);
        fwrite($conf,'- APubID = '.$this->APubID.PHP_EOL);
        fflush($conf);fclose($conf);
        $conf = fopen('la_redirect.md','w');
        fwrite($conf,$this->DisplayRedirectConfig());fflush($conf);fclose($conf);
        $this->WriteHTACCESS();
        $this->WriteTokens();
    }
    
    function Install(){
        if(!file_exists('la_config.php')){
            $this->WriteConfig();
        }
        if(!is_dir('posts')) mkdir('posts');
        if(!is_dir('images')) mkdir('images');
        if(!is_dir('images/thumb')) mkdir('images/thumb');
        if(!is_dir('styles')) mkdir('styles');
        if(!is_dir('archive')) mkdir('archive');
        
        $this->WriteStyles();
        $this->WriteHTACCESS();
    }
    
    function ReadFromExistingConfig(){
        $f=null;
        if(file_exists('la_config.php')) $f='la_config.php';
        else if(file_exists('la_config.md')) $f='la_config.md';
        if(!isset($f)) return;
        $c = file_get_contents($f);
        if(preg_match_all('/-\s*(\S+)\s*=\s*(\S+)\s*$/um', $c, $ma, PREG_SET_ORDER)) foreach($ma as $m){
            $str = $m[1];
            $this->$str = $m[2];
        }
        $this->CommentEnabled = $this->CommentEnabled=="True";
    }
    
    function ReadConfig(){
        $this->ReadFromExistingConfig();
        if(file_exists('la_redirect.md')){
            $c = file_get_contents('la_redirect.md');
            $this->BuildRedirectConfig($c);
        }
        if(!file_exists('la_config.php')){
            $this->Install();
        }
        $this->Translations=[];
        if(file_exists("translations.md")){
            $c = file_get_contents('translations.md');
            if(preg_match_all('/-\s+(\S.*)\s*\|\s*(\S.*)$/um',$c, $ma, PREG_SET_ORDER)) foreach($ma as $m){
                $entry = []; $entry['zh'] = trim($m[1]); $entry['en'] = trim($m[2]);
                $this->Translations[] = $entry;
            }
        }
        if(file_exists("custom_translations.md")){
            $this->CustomTranslationContent = file_get_contents('custom_translations.md');
            if(preg_match_all('/-\s+(\S.*)\s*\|\s*(\S.*)$/um',$this->CustomTranslationContent, $ma, PREG_SET_ORDER)) foreach($ma as $m){
                $entry = []; $entry['zh'] = trim($m[1]); $entry['en'] = trim($m[2]);
                $this->Translations[] = $entry;
            }
        }
        $this->LoginTokens=[];
        if(file_exists('la_tokens.php')){
            $c = file_get_contents('la_tokens.php');
            if(preg_match_all('/-\s+(\S.*)\s*$/um',$c, $ma, PREG_SET_ORDER)) foreach($ma as $m){
                $this->LoginTokens[] = $m[1];
            }
        }
    }
    
    function MastodonPostStatus($status){ return $this->MastodonCall('/api/v1/statuses', 'POST', $status); }
    function MastodonPostMedia($media){ return $this->MastodonCall('/api/v1/media', 'POST', $media); }
    function MastodonCall($endpoint, $method, $data){
        $headers = [
            'Authorization: Bearer '.$this->MastodonToken,
            'Content-Type: multipart/form-data',
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->MastodonURL.$endpoint);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $reply = curl_exec($ch);

        if (!$reply) {
            return json_encode(['ok'=>false, 'curl_error_code' => curl_errno($ch_status), 'curl_error' => curl_error(ch_status)]);
        }
        curl_close($ch);

        return json_decode($reply, true);
    }
    function MastodonSendPost(&$post, &$errmsg=NULL){
        if(isset($post['mark_value']) && $post['mark_value']==7){ return NULL; }
        
        $this->LanguageAppendix = 'zh';
        if(in_array($this->MastodonPreferredLang,['en','zh'])){ $this->LanguageAppendix = $this->MastodonPreferredLang; }
        
        $this->ConvertPost($post);
        $media_ids = NULL;
        $mastodon_post_url = NULL;
        
        $imcount = 0;
        if(isset($post['original_images']) && isset($post['original_images'][0])) foreach($post['original_images'] as $im){
            $curl_file = curl_file_create($im, mime_content_type($im), basename($im));
            $body = ['file' => $curl_file,]; $response = $this->MastodonPostMedia($body);
            if(isset($response['id'])){ if(!isset($media_ids)){$media_ids=[];} $media_ids[]=$response['id']; }
            else{ if(isset($response['error'])) $errmsg.=$response['error']."<br /><br />"; }
            $imcount++; if($imcount>=4){ break; }
        }
        
        $text = strip_tags(preg_replace('/<\/(p|blockquote|h[0-9])>/u',"\n\n",$post['html']));
        if(isset($this->HostURL) && $this->HostURL!=""){
            $text.=("\n\n".$this->T('来自').' '.$this->HostURL.'?post='.$post['id']);
        }
        
        $vis = (isset($post['mark_value']) && $post['mark_value']==6)?"private":"public";
        $status_data = [ 'status' => $text, 'visibility' => $vis, 'language' => $this->LanguageAppendix, ];
        if(isset($post['prev']) && ($pp=&$this->GetPost($post['prev']))!=$this->NULL_POST){
            if(isset($pp['mastodon_url'])){ $status_data['in_reply_to_id'] = basename($pp['mastodon_url']); 
            }
        }
        if(isset($media_ids[0])){ $status_data['media_ids']=$media_ids; }
        $status_data = preg_replace('/(media_ids%5B)[0-9]+(%5D)/','$1$2', http_build_query($status_data));
        
        $status_response = $this->MastodonPostStatus($status_data);
        if(isset($status_response['url']) && $status_response['url']){
            $mastodon_post_url = $status_response['url'];
            $post['mastodon_url'] = $mastodon_post_url;
            $this->NeedWritePosts = 1;
        }
        if(isset($status_response['error'])) { $errmsg .= $status_response['error']; }
        
        return $mastodon_post_url;
    }
    
    function __construct() {
        $this->ReadConfig();
        $this->PDE = new ParsedownExtra();
        $this->PDE->SetInterlinkPath('/');
        $this->Posts = [];
        $this->Threads = [];
        $this->VisitedHere = [];
        
        $this->NULL_IMAGE_DUMMY = [];
        $this->NULL_IMAGE_DUMMY['name']=$this->NULL_IMAGE_DUMMY['file']=$this->NULL_IMAGE_DUMMY['thumb']="";
        
        $this->Markers=['●', '○', '✓', '×', '!', 'P', 'E', 'S'];
        
        $this->PostsPerPage = 40;
        $this->CommentsPerPage = 100;
        $this->HotPostCount = 15;
        
        $this->TIME_STRING = date('YmdHis');
        $this->WayBack = NULL;
        $this->YearBegin = $this->YearEnd = 2000;
        $this->DoneReadPosts = $this->DoneReadImages = $this->DoneReadArchive = false;
        $this->NeedWritePosts= $this->NeedWriteImages= $this->NeedWriteArchive= false;
        $this->UsePosts = &$this->Posts;
    }
    
    function DoLogout(){
        $this->LoggedIn = false;
        unset($_SESSION['user_id']);
        $this->RecordToken(true);
    }
    
    function RecordToken($unset_current=false){
        if(isset($unset_current) && isset($_COOKIE['la_token'])){
            $t = $_COOKIE['la_token'];
            setcookie('la_token', null, -1); unset($_COOKIE['la_token']);
            if (($key = array_search($t,$this->LoginTokens)) !== false) { unset($this->LoginTokens[$key]); }
            $this->WriteTokens();
            return null;
        }else{
            $t = uniqid('la_',true);
            setcookie('la_token',$t,time()+3600*24*7); $_COOKIE['la_token'] = $t;
            $this->LoginTokens[] = $t;
            $this->WriteTokens();
            return $t;
        }
    }
    
    function LoginThroughToken(){
        if(!isset($_COOKIE['la_token'])) return false;
        $t = $_COOKIE['la_token'];
        if (($key = array_search($t,$this->LoginTokens)) !== false) {
            $_SESSION['user_id']=$this->Admin;
            $this->LoggedIn = true;
            setcookie('la_token',$t,time()+3600*24*7);
            return true;
        }
        return false;
    }
    
    function DoLogin(){
        session_start();
        $redirect=false;
        if(isset($_GET['logout'])){ $this->DoLogout(); header('Location:index.php'); }
        else if(!isset($_SESSION['user_id'])){
            if(isset($_POST['login_button'])){
                $id = trim($_POST['login_id']);
                $pwd = trim($_POST['login_password']);
                if(strtolower($this->Admin)==strtolower($id)&&password_verify($pwd, $this->Password)){
                    $_SESSION['user_id']=$id;
                    $this->RecordToken(false);
                }
                $redirect = true;
            }else if($this->LoginThroughToken()){
                // nothing;
            }
        }else{
            if(strtolower($_SESSION['user_id']) == strtolower($this->Admin)){ $this->LoggedIn = true; }
            else{ $this->DoLogout();}
        }
        if($redirect){
            header('Location:index.php'.(isset($_GET['post'])?("?post=".$_GET['post']):"")
                                       .(isset($_GET['settings'])?"?settings=true":""));
        }
    }
    function SetHereMainImage($im_this){
        if(isset($im_this) && isset($im_this['parent'])){ $this->HereMainImage = &$this->FindImage($im_this['parent'],true);}
    }
    function RecordVisitedHere($image_or_post_name, $image_title){
        $visited_here = $this->InExperimentalMode?"visited_experimental":"visited_here";
        if(!isset($_SESSION[$visited_here])){ $_SESSION[$visited_here] = []; }
        if(!(isset($_SESSION[$visited_here][0]) && in_array($image_or_post_name,$_SESSION[$visited_here]))){
            $_SESSION[$visited_here][] = $image_or_post_name;
        }
        if(isset($image_title)){ $this->HereDisplayTitle=$image_title; }
        $this->HereNumber = array_search($image_or_post_name,$_SESSION[$visited_here])+1;
        $_SESSION['here_number'] = $this->HereNumber;
        $this->VisitedHere = $_SESSION[$visited_here];
    }
    function HereLinkFromNumber($number){
        $number -= 1; if($number<0){ $number=0; }
        if($number>=sizeof($this->VisitedHere)){ $number = sizeof($this->VisitedHere)-1; }
        if(preg_match('/(jpg|jpeg|png|gif)/u',$this->VisitedHere[$number])){ return "?here=images/".$this->VisitedHere[$number]; }
        else{ return "?post=".$this->VisitedHere[$number]; }
    }
    
    function WriteStyles(){
        $this->style="
html{font-size:18px;font-family:'Noto Serif CJK SC','Times New Roman','SimSun', Georgia, serif;}
body{background-color:%white%;color:%black%;}
sup,sub{line-height:0;}
blockquote{border-left:2px solid %black%;padding-left:0.3em;}
*{box-sizing:border-box;padding:0;margin:0;font-weight:normal;}
b,strong,th{font-weight:bold;}
select{font-size:inherit;}
.page,.page_gallery{padding:1em;padding-top:0;padding-bottom:0;}
.hidden_on_desktop,.hidden_on_wide{display:none;}
.hidden_on_desktop_force{display:none !important;}
::file-selector-button{background:none;border:none;}
a,button,::file-selector-button{text-decoration:underline;color:%black%;}
a:hover,.button:hover,::file-selector-button:hover{text-decoration:none;color:%gray%;}
.button:disabled{background-color:%gray%;pointer-events:none;}
header{position:sticky;top:0;background-color:%white%;z-index:10;padding-top:0.5em;max-height:100vh;z-index:30;}
header::before{content:'';position:absolute;left:0;right:0;top:0;height:1.95em;box-shadow:-2em 0em 1em -1em inset %white%;pointer-events:none;}
header>div{overflow:auto;white-space:nowrap;}
.header_nav{display:inline;}
header a,.left a,.footer a,.clean_a,.clean_a a{text-decoration:none;}
header a:hover,.button:hover{color:%gray% !important;}
.exp_h_f{padding-top:0.3em !important;padding-bottom:0.3em !important;line-height:1.5em !important;height:2.1em !important;}
.exp_f{text-align:center;}
.toc_button{position:absolute;top:0.5em;right:0;text-shadow: 0px 0px 10px %white%;background-color:%white%88;}
.footer{background-color:%white%;z-index:10;position:relative;}
.invert_a,.invert_a a{color:%gray%;text-decoration:none;}
.invert_a:hover,.invert_a a:hover{color:%black% !important;}
.gray,.gray a{color:%gray%;}
hr{border:1px solid %gray%;}
header ul,.small_footer ul,.small_footer span,header li,.small_footer li{display:inline-block;}
header li::before,.small_footer li::before{content:' - '}
header h1,header h2,header h3,header h4,header h5,header p{display:inline;font-size:1rem;}
.main{position:relative;word-spacing:-1em;}
.main *{word-spacing:initial;}
pre{overflow:auto;max-width:100%;display:block;font-size:0.75em;}
ul{display:block;}
li{display:block;}
.clean_table{border:none;font-size:1em !important;}.clean_table thead{box-shadow:none;}.clean_table td{vertical-align:top;}
table{width:100%;border-collapse:collapse;border-bottom:2px solid %black%;border-top:3px solid %black%;}
table input{border:none!important;}
table img{max-width:10rem !important;}
td{padding-left:0.1em;padding-right:0.1em;}
td:first-child{padding-left:0;}
td:last-child{padding-right:0;}
tbody tr:hover{box-shadow:inset 0 -2px 0 0px %black%;}
thead{box-shadow:inset 0 -1px 0 0px %black%;position:sticky;top:2rem;background-color:%white%;z-index:5;}
.post table{font-size:0.85em;}
.interesting_tbody{background:linear-gradient(90deg, %white%ff, %white%88 20em);}
.interesting_tbody td{display:contents;}
.interesting_tbody tr{position:relative;scroll-margin:4.5em}
.interesting_tbody td>*{display:table-cell;}
.interesting_tbody td>.wscroll{display:none !important;}
.interesting_tbody .post_access{padding-top:0;}
.interesting_tbody .post_menu_button{top:0;opacity:0;}
.interesting_tbody td>img,.interesting_tbody td>.imd{position:absolute;left:1.4em;z-index:-1;height:1em;width:20em;
display:flex;top:0.2em;object-fit:cover;max-width:calc(100% - 1.4em) !important;overflow:hidden;}
.interesting_tbody td>.imd>a{display:flex;}
.interesting_tbody img{object-fit:cover;width:100%;}
.interesting_tbody .p_row{display:flex;position:absolute;left:1.4em;top:0.25em;z-index:-1;flex-wrap:nowrap;max-width:calc(100% - 1.4em);}
.interesting_tbody .p_thumb{height:1em;}
.interesting_tbody .p_thumb img,.interesting_tbody .p_thumb video{max-height:10rem !important;max-width:20rem !important;}
tr:hover .post_menu_button{opacity:1;}
.post_current_row{background-color:%graybkg%;mix-blend-mode:screen;text-shadow:0px 0px 0.1em %white%;}
.align_right{text-align:right;}
.left{display:inline-block;vertical-align:top;width:25%;max-height:calc(100vh - 2.6em);top:2em;
position:sticky;overflow:auto;padding-right:0.2em;padding-bottom:4rem;}
.center{display:inline-block;vertical-align:top;width:50%;padding-left:0.3em;overflow:visible;padding-bottom:4rem;}
.center_wide{display:inline-block;vertical-align:top;width:75%;padding-left:0.3em;overflow:visible;padding-bottom:4rem;}
.center_full{display:inline-block;vertical-align:top;width:100%;overflow:visible;padding-bottom:4rem;}
.center_wide .p_thumb{height:10rem;}
.sticky_title{position:sticky;top:calc(1.6rem + 2px);z-index:1;box-shadow:6em 3.5em 0.75em -3em inset %white%;pointer-events:none;}
.center_exp{display:block;width:80%;margin:0 auto;overflow:visible;padding-bottom:1em;}
.table_top{position:relative;left:calc(-50% - 0.45em);width:calc(200% + 0.6em);background:%white%;z-index:1;
box-shadow:0px 0px 2em 1em %white%;margin-top:2em;margin-bottom:2em;}
.right{display:inline-block;vertical-align:top;width:25%;position:sticky;top:2em;
padding-left:0.5em;max-height:calc(100vh - 2.6em);overflow:auto;padding-bottom:4rem;}
textarea,input[type=text],input[type=password]{width:100%;display:block;font-family:inherit;max-height:60vh;font-size:inherit;}
select,textarea,input[type=text],input[type=password]{background:none;border:none;border-bottom:1px solid %black%;color:%black%;}
.text_highlight input{border-bottom:1px solid %white%;color:%white%;}
.button{background:none;border:none;font-family:inherit;color:%black%;font-size:inherit;font-weight:bold;}
.post{position:relative;scroll-margin:3.5em;border-radius:0.3em;}
.center_exp .post{padding-left:0;padding-right:0;padding-top:0;padding-bottom:0;border-radius:0;}
.post_width li,.post_width_big li,.footer_additional li,.footer_additional li,.post_dummy li
{display:list-item;margin-left:1em;list-style:disc;}
.post_width li li,.post_width_big li li,.footer_additional li li,.footer_additional li li,.post_dummy li li
{list-style:circle;}
ol li{list-style:unset !important;}
.post_width > *,.post_width_big > *,.post_dummy > *,.post_ref > *{margin:0;margin-bottom:0.5rem}
.post_dummy > *{width:60%;margin:0 auto;margin-bottom:0.5rem}
.post_dummy > p img{display:block;width:100%;margin:0 auto;}
.gallery_left li{display:list-item;margin-left:1em;list-style:none;}
.gallery_left .selected{list-style:'→';}
.focused_post{font-size:1.1em;margin-top:0.1em;margin-bottom:0.1em;padding:0.5rem !important;border:2px dashed #ac7843;}
.post_width{position:relative;left:1.4rem;width:calc(100% - 1.7rem);padding-left:0.2em;overflow:visible;}
.post_width_big{position:relative;left:0;width:100%;overflow:visible;}
.post_menu_button{position:absolute;display:none;right:0rem;width:1.5rem;
text-align:center;border-radius:0.3em;user-select:none;cursor:pointer;z-index:10;}
.pointer{cursor:pointer;}
.post:hover .post_menu_button{display:block;}
.pop_menu{position:absolute;top:0.3rem;z-index:95;background-color:%lighterbkg%;
padding:0.3em;right:0.3rem;text-align:right;border-radius:0.3em;font-size:1rem;
box-shadow:0px 0px 10px rgb(0, 0, 0);}
.pop_menu li{list-style:none;margin-left:0;}
.pop_menu hr{border:2px solid rgba(0,0,0,0.1);}
.toc{left:60%;width:40%;top:0;position:absolute;}
.post_access{width:1.4rem;top:0;position:absolute;height:100%;text-align:center;font-weight:bold;border-right:2px solid transparent;user-select:none;}
.post_access:hover{background-color:%lightopbkg%;border-top-left-radius:0.3em;border-bottom-left-radius:0.3em;
border-right:2px solid %black% !important;}
.paa{width:1.4rem;min-width:1.4rem;}
.opt_compact .post_access,.ref_compact .post_access{border-right:2px solid %gray%;}
.post_box{border:1px solid %gray%;border-radius:0.3em;padding:0.3em;}
.post_box:hover,.post_menu_button:hover{background-color:%lightopbkg%}
#big_image_info .post_box:hover{background-color:%graybkg%;}
.post_preview{font-size:0.9rem;overflow:hidden;margin-bottom:0.2em;}
.post .post_ref{margin:0;padding-left:1.7rem;}
.post_ref_main{display:inline-block;vertical-align:top;}
.post_preview .post_ref_main{max-height:6rem;overflow:hidden;}
.post_ref_images{overflow:hidden;}
.page_selector{padding-top:2rem;text-align:center;}
.smaller{font-size:0.85em;}
.bigger{font-size:1.3em;}
.block{display:block;}
.opt_compact{margin-left:1.6rem;}
.opt_compact .post_width {margin-left:0.3em;width: calc(100% - 1.8rem);}
.post_box_top{padding-bottom:0.3em;padding-top:0.3em;}
.post_box_fixed_bottom{position:sticky;bottom:0em;background-color:%white%;z-index:5;}
.spacer{height:0.5em;}
.pop_right,.pop_right_big{position:fixed;top:0;right:0;bottom:0;width:30%;z-index:100;background-color:%graybkg%;display:none;
transition-timing-function:ease-out;padding:1rem;overflow:auto;}
@keyframes pop_slide_in{0%{right:-30%;}100%{right:0%;}}
@keyframes pop_slide_out{0%{right:0%;}100%{right:-30%;}}
@keyframes pop_slide_in_big{0%{right:-30%;}100%{right:0%;}}
@keyframes pop_slide_out_big{0%{right:0%;}100%{right:-30%;}}
.backdrop{position:fixed;top:0;right:0;bottom:0;left:0;background-color:rgba(0,0,0,0.5);transition-timing-function:ease-out;z-index:90;}
@keyframes backdrop_fade_in{0%{opacity:0%;}100%{opacity:100%;}}
@keyframes backdrop_fade_out{0%{opacity:100%;}100%{opacity:0%;}}
.toc_entry_1{font-size:1.1em;}
.toc_entry_2{font-size:1.0em;padding-left:0.5rem;}
.toc_entry_3{font-size:0.9em;padding-left:1rem;}
.toc_entry_4{font-size:0.85em;padding-left:1.5rem;}
.toc_entry_5{font-size:0.8em;padding-left:2rem;}
h1,h2,h3,h4,h5{scroll-margin:2.5em;}
.left ul h1,.left ul h2,.left ul h3,.left ul h4,.left ul h5,.left ul p{font-size:1em;}
.deleted_post{color:%gray%;text-decoration:line-through;}
#file_list{margin-top:0.5em;}
.file_thumb img,.file_thumb video{max-height:100%;max-width:100%;object-fit:cover;min-width:100%;min-height:100%;}
.file_thumb video{border:2px dashed %black%;}
#file_list li{margin-bottom:0.3em;}
.ref_thumb{white-space:nowrap;overflow:hidden;}
.ref_thumb .file_thumb{width:3em;height:3em;}
.side_thumb li{margin:0.4em;display:inline-block;}
.file_thumb{width:4em;height:4em;display:inline-block;line-height:0;vertical-align:middle;overflow:hidden;}
.p_row{display:flex;flex-wrap:wrap;width:100%;}
.p_thumb{display:flex;flex-grow:1;height:6rem;margin-right:0.25rem;margin-bottom:0.25rem;overflow:hidden;position:relative;}
.p_thumb_narrow{width:1rem;}
.p_thumb img,.p_thumb video{object-fit:cover;max-height:100%;min-width:100%;}.p_thumb a{display:contents;}
.ref_count,.p_thumb .post_menu_button{text-shadow: 0px 0px 10px rgb(0, 0, 0);}
.p_thumb:hover .post_menu_button{display:block;}
.p_thumb_selected{color:%black% !important;}
.p_thumb_selected{display:block;}
.post .p_thumb img,.post .p_thumb video{max-height:6rem;}
.p_thumb video{border:2px dashed %black%;}
.big_image_box{position:fixed;top:0;bottom:0;left:0;width:75%;z-index:95;text-align:center;pointer-events:none;}
.big_image_box *{pointer-events:auto;}
.big_image_box img,.big_image_box video{position:absolute;margin:auto;top:0;left:0;right:0;bottom:0;cursor:unset;}
.here_image_box{position:relative;width:100%;text-align:center;height:calc(100vh - 4.5em);}
.here_image_box img,.here_image_box video{position:absolute;margin:auto;top:0;left:0;right:0;bottom:0;cursor:unset;}
.big_side_box{position:fixed;top:0;bottom:0;right:0;width:25%;overflow:auto;z-index:98;color:%black%;padding:1rem;pointer-events:none;
background:linear-gradient(to right, rgba(0,0,0,0), rgb(1, 1, 1));transition:background-size .2s linear;background-size: 300% 100%;}
.big_side_box:hover{background-size: 100% 100%;}
.big_side_box a,.big_side_box hr,#dropping_background{color:%black%;}
.big_side_box a:hover{color:%gray%;}
.big_side_box *{pointer-events:auto;}
.image_nav{pointer-events:none;}
#dropping_background{background-color:rgba(0,0,0,0.4);position:fixed;top:0;right:0;bottom:0;left:0;z-index:100;text-align:center;
box-shadow:0px 0px 500px black inset;display:flex;align-items:center;}
img,video{cursor:pointer;max-height:100%;max-width:100%;vertical-align:middle;}
.post img,.post video{max-height:min(70vh, 20rem);max-width:min(100%, 20rem);}
.post > a > img,.post > a > video{display:block;margin:0.3em auto;}
.post .original_img{max-width:100%;display:inline-block;vertical-align:middle;
margin-left:auto;margin-right:auto;max-width:100%;max-height:90vh;}
.center_exp .post .original_img{display:block;}
.original_img img,.original_img video{max-height:90vh;max-width:100%;}
.p_row .original_img{margin-bottom:0;}
.post_ref .original_img{margin:unset;max-width:unset;max-height:min(70vh, 20rem);max-width:min(100%, 20rem);}
.b ul{font-size:1.4em;}
no_pop{cursor:unset;}
p{min-height:0.8em;}
.bold,.bold *{font-weight:bold;}
.footer_additional{display:inline-block;width:50%;vertical-align:text-top;white-space:normal;}
.small_footer{background-color:%white%;padding-bottom:0.5em;position:sticky;bottom:0px;z-index:10;}
.small_footer>div{overflow:auto;white-space:nowrap;}
.small_footer::before{content:'';position:absolute;left:0;right:0;bottom:0;height:1.95em;box-shadow:-2em 0em 1em -1em inset %white%;pointer-events:none;}
.top_post_hint{margin-left:1.5em;font-weight:bold;}
.white{color:%white%;}
.full_box{border:1px solid %black% !important;padding:0.3rem;overflow:auto;}
.image_nav_prev,.image_nav_next{z-index:100;position:absolute;line-height:0;height:50%;width:20%;top:25%;display:flex;align-items:center;
transition:background-size .2s ease;padding:0.5em;text-shadow:0px 0px 5px black;user-select:none;pointer-events:auto;}
.image_nav_prev{left:0;justify-content:left;background:linear-gradient(to left, rgba(0,0,0,0), rgba(0,0,0,0.4));
background-repeat:no-repeat;background-size:0% 100%;}
.image_nav_prev:hover,.image_nav_next:hover{background-size:100% 100%;}
.image_nav_next{right:0;justify-content:right;background:linear-gradient(to right, rgba(0,0,0,0), rgba(0,0,0,0.2));
background-repeat:no-repeat;background-size:0% 100%;transition:background-size .2s ease;background-position-x:100%;}
.inquiry_buttons{position:fixed;left:0;right:25%;text-align:center;bottom:1em;margin:0 auto;width:max-content;
background-color:rgba(0,0,0,0.5);z-index:110;padding:0.2em;padding-left:1em;padding-right:1em;
border-radius:1em;box-shadow:0px 0px 5px;text-shadow:0px 0px 5px black;opacity:1;user-select:none;}
.lr_buttons{background-color:rgba(0,0,0,0.5);padding:0.5em;padding-top:1em;padding-bottom:1em;
border-radius:1em;box-shadow:0px 0px 5px;font-size:1.2rem;text-shadow:0px 0px 5px black;}
.img_btn_hidden{opacity:0;transition:opacity 0.2s;}
.special_alipay{background-color:#027aff;color:white;white-space:nowrap;
font-family:sans-serif;font-weight:bold;border-radius:0.7em;font-size:0.75em;padding:0.25em;}
.special_paypal{background-color:white;color:#253b80;white-space:nowrap;
font-family:sans-serif;font-weight:bold;border-radius:2em;font-size:0.75em;
padding:0.25em;padding-left:0.5em;padding-right:0.65em;font-style: italic;}
.special_paypal_inner{color:#169bd7;}
#waiting_bar{position:fixed;z-index:200;top:0;left:0;right:0;height:0.2em;background-color:%black%;transform:translate(-100%,0);
animation:anim_loading 1s linear infinite;}
@keyframes anim_loading{0%{transform:translate(-100%,0);} 100%{transform:translate(100%,0);}}
.product_ref{width:32%;padding:0.2em!important;display:inline-block;text-align:center;vertical-align:top;margin-bottom:0.8em;}
.product_thumb{max-height:11em;max-width:11em;display:inline-flex;margin-bottom:0.2em;background-color:%graybkg%;}
.product_thumb img,{box-shadow:none;object-fit:contain;max-height:unset;max-width:unset;width:100%;margin:0 auto !important;}
.product_ref p{margin-bottom:0.2em;text-align:left;}
.post_preview .product_thumb{max-height:4em;max-width:6em;}
.purchase_button{background-color:%black%;color:%white%;padding-left:0.5em;padding-right:0.5em;text-decoration:none;font-weight:bold;}
.page_break{page-break-after:always;}
.text_highlight,.text_highlight a,.text_highlight select{background-color:%black%;color:%white%;border:none;}
.gray.text_highlight,.gray.text_highlight a,.gray.text_highlight select{background-color:%gray%;color:%white%;}
.print_title{display:none;}
.show_on_print{display:none;}
.comment{font-size:0.9em;font-family:sans-serif;overflow:auto !important;width:100%;}
.comment tbody tr:hover{box-shadow:none;}
.comment table{border:none;}
.comment li{display:list-item;list-style:'→';padding-left:0.3em;}
.comment ul{padding-left:1em;}
.comment ul li *{margin-bottom:0.5em;}
.history li{display:list-item;list-style:disc;padding-left:0.3em;}
.history li li{list-style:circle;}
.history ul{padding-left:1em;}
.history a{text-decoration:underline;}
.history .list{overflow:auto;white-space:nowrap;}
.diff_table{table-layout:fixed;}
.diff_table thead{font-size:0.9em;text-align:center;}
.diff_table tbody pre{font-size:0.9rem;white-space:pre-line;}
.diff_table td{vertical-align:top;}
.omittable_title{display:block;width:100%;text-overflow:ellipsis;overflow:hidden;white-space:nowrap;}
.wayback_close{float:right;}
.wayback_expand{display:inline;}
.post_selecting .post *{pointer-events:none;}
.post_selected{background-color:%graybkg%;}
.small_pad{padding:0.2em;padding-top:0.1em;padding-bottom:0.1em;}
.wscroll{scroll-margin:3.5em;padding-left:0.3em;display:none;font-weight:bold;font-size:0.75em;box-shadow: 13em 0em 4em -8em inset %gray%;color:%white%;}
.wscroll:target{display:block;} .post_ref .wscroll{display:none !important;}
.wayback_link{display:inline;}
.imd{object-fit:cover;width:100%;}
.center_exp .imd{width:60%;}
#here_buttons{display:contents;}
.here_buttons_inner{position:absolute;margin:auto;top:0;left:0;right:0;bottom:0;max-height:100%;max-width:100%;}
.here_btn{position:absolute;z-index:110;transform:translate(-50%,-50%);
width:2em;height:2em;display:block;border:1px solid %black%;background-color:rgba(0,0,0,0.5);}
.round_btn{box-shadow: 0px 0px 0px 1px inset %black%;display:inline-block;padding-left:0.4em;padding-right:0.4em;border-radius:0.5em;}

@media screen and (max-width:1000px) and (min-width:666px){
.left{width:35%;}
.center,.center_wide{width:65%;}
.center_wide .p_thumb{height:8rem;}
.right{display:none;}
.post_width{width:calc(100% - 1.5rem);padding-left:0.2em;}
.post_width_big{left:0;width:100%;}
.hidden_on_wide{display:unset;}
.pop_right{width:30%;}
.pop_right_big{width:40%;}
@keyframes pop_slide_in{0%{right:-30%;}100%{right:0%;}}
@keyframes pop_slide_out{0%{right:0%;}100%{right:-30%;}}
@keyframes pop_slide_in_big{0%{right:-40%;}100%{right:0%;}}
@keyframes pop_slide_out_big{0%{right:0%;}100%{right:-40%;}}
.big_side_box{width:35%;}
.big_image_box{width:65%;}
.inquiry_buttons{right:35%;}
.table_top{left:calc(-50% - 1.7em);width:calc(154% + 0.5em);}
.post_dummy > *{width:80%;max-width:55rem;}
.center_exp .imd{width:80%;}
.here_btn{width:1.5em;height:1.5em;}
}

@media screen and (max-width:666px){
html{font-size:16px;}
.hidden_on_mobile{display:none !important;}
.block_on_mobile{display:block !important;}
.hidden_on_desktop{display:unset;}
.hidden_on_wide{display:unset;}
header ul{display:block;}
header li{display:block;}
header li::before{content:''}
header::before{box-shadow:none;display:none;}
.small_footer::before{box-shadow:none;display:none;}
.left{position:relative;width:100%;position:relative;top:unset;height:unset;min-height:80vh;padding-right:0;display:block;}
.center,.center_wide,.center_full{position:relative;left:0;top:0;width:100%;padding-left:0;display:block;}
.center_wide .p_thumb{height:6rem;}
.pop_right,.pop_right_big{top:unset;right:0;bottom:0;left:0;width:100%;}
.pop_right{height:30%;}
.pop_right_big{height:70%;}
@keyframes pop_slide_in{0%{bottom:-30%;}100%{bottom:0%;}}
@keyframes pop_slide_out{0%{bottom:0%;}100%{bottom:-30%;}}
@keyframes pop_slide_in_big{0%{bottom:-70%;}100%{bottom:0%;}}
@keyframes pop_slide_out_big{0%{bottom:0%;}100%{bottom:-70%;}}
.big_image_box{position:fixed;top:0;bottom:8.5rem;left:0;right:0;width:100%;}
.side_box_mobile_inner{background:linear-gradient(to bottom, rgba(0,0,0,0), rgba(1,1,1,0.9) 20%);
transition:none;background-size:100% 100%;padding:0.5rem;padding-bottom: 5em;}
.side_box_mobile_inner:hover{background-size:100% 100%;}
.big_side_box{position:fixed;top:0;bottom:0;right:0;left:0;width:100%;
height:unset;padding:0;padding-top:calc(100vh - 8.5rem);background:none;}
.p_thumb{height:3rem;}
.center .post{padding-right:0rem;padding-left:0rem;}
.post .p_thumb img,.post .p_thumb video{max-height:3rem;}
.page,.page_gallery{padding:0.2em;padding-top:0;}
header{padding-top:0.3em;}
.toc_button{top:0.3em;}
.small_footer{padding-bottom:0.3em;}
.footer_additional{display:block;width:100%;}
.album_hint{display:block;font-size:1rem;}
.image_nav{position:absolute !important;}
.image_nav_prev,.image_nav_next{width:25%;}
.image_nav_prev:hover,.image_nav_next:hover{background-size:0% 100%;color:%black% !important;}
.inquiry_buttons{position:relative;left:unset;right:unset;text-align:left;bottom:unset;margin:unset;width:unset;
background-color:unset;z-index:unset;padding:unset;padding-left:unset;padding-right:unset;
border-radius:unset;box-shadow:unset;text-shadow:unset;}.img_btn_hidden{opacity:1;}
.lr_buttons{background-color:unset;padding:unset;padding-top:unset;padding-bottom:unset;
border-radius:unset;box-shadow:unset;font-size:1.3rem;text-shadow:unset;}
.opt_compact,.ref_compact{line-break:anywhere;}
.post_width,.post_width_big{overflow:auto;}
.table_top{left:unset;width:100%;overflow:auto;}
table img{max-width:30vw !important;}
.product_ref{width:100%;display:block;}
.post_dummy > *{width:100%;max-width:25rem;}
.sticky_title{top:1.2em;}
.small_footer{position:relative;}
#upload_selector{width:100%;}
.focused_post{padding:0.3rem !important;}
.interesting_tbody{background:linear-gradient(90deg, %white%ff, %white%88 10em);}
.wayback_expand{display:block;text-align:center;}
.wayback_link{display:block;}
.center_exp{display:block;width:100%;margin:0 auto;padding-bottom:1em;}
.center_exp .post{overflow:auto;}
.center_exp .imd{width:100%;}
.here_btn{width:1.2em;height:1.2em;}
.exp_h_f{height:unset !important;}
.exp_f{margin-bottom:1em;}
}

@media print{
header b,.small_footer b{font-weight:normal;}
header{display:table-header-group;} .main{display:table-row-group;} .small_footer{text-align:right;}
header::before{box-shadow:none;display:none;} header::after{display:block;height:1em;content:' ';}
.small_footer::before{box-shadow:none;display:block;height:1em;position:relative;}
body,footer,header,.small_footer,a,.clean_a,.invert_a,.clean_a a,.invert_a a{background:none;color:black;}
.post *,.post_dummy *{margin-bottom:0em}
.p_row,.post table,.post_width>img,.post_width_big>img,.post_ref>img,.post_ref>.original_img,
.post_width>.original_img,.post_width_big>.original_img,.post pre{margin-top:0.5rem;margin-bottom:0.5rem;text-indent:0;}
pre{white-space:pre-wrap;line-break:anywhere;}
.post p{line-height:1.3;text-indent:2em;} .product_ref p {text-indent:0;}
table img{margin:0 !important;}
.post h1+p,.post h2+p,.post img+p,.post video+p,.post .imd+p,.post table+p,.last_wide p:first-of-type{text-indent:0;}
.post ul,.post ol{margin:1rem;margin-left:1.4rem;margin-right: 0rem;}
table{border-bottom:2px solid black;border-top:2px solid black;}
table img{max-width:5em;max-width:8em !important;max-height:8em !important;}
thead{box-shadow:inset 0 -1px 0 0px black;background:none;}
.post_width,.post_width_big{overflow:clip;left:0;width:100%;padding-left:0em;}
.post h1{margin-top:0.5rem;}
.post h2{font-size:1.8em;margin:2.5em auto 0;}.list h2,.opt_compact h2,.ref_compact h2{margin:0 !important;}
.post h3{font-size:1.5em;margin:1.5em auto 0;}.list h3,.opt_compact h3,.ref_compact h4{margin:0 !important;}
.post h4{font-size:1.1em;margin:0.5em auto 0;}.list h4,.opt_compact h3,.ref_compact h4{margin:0 !important;}
.post .post{margin-bottom:0.5rem;margin-top:0.5rem;}
.gray,.gray a,.deleted_post{color:rgba(0,0,0,0.5);}
.left,.right{display:none;}
.center, .center_wide, .center_full{width:100%;padding:0;display:block;font-size:16px;line-height:1.3}
hr{border:1px solid black;}
.post_box_top{display:none;}
.opt_compact .post_access,.ref_compact .post_access{border-right:none;display:inline;}
.text_highlight,.text_highlight a,.gray.text_highlight,.gray.text_highlight a,.purchase_button{background-color:lightgray;color:black;}
.focused_post{border:none;font-size:1em;padding:0 !important;}
.hidden_on_print{display:none;}
.print_column{column-count:2;margin-top:0.5rem;margin-bottom:0.5rem;}
.post_access{display:none;}
.opt_compact{margin-left:0;}
.post .post_ref{padding-left:1.4rem;}
.opt_compact .post_width{left:1.4rem;width:calc(100% - 1.4rem);margin-left:0;}
.print_title{column-span:all;display:block;margin-top:2em;margin-bottom:0.5rem;font-size:1.2em;}
.print_title:first-of-type{margin-top:1em;}
.print_title+.post h1:first-of-type{display:none;}
.opt_compact h1:first-of-type,.ref_compact h1:first-of-type{display:unset;}
.table_top{position:relative;left:0;width:100%;background:none;z-index:1;box-shadow:none;}
.header_nav{display:none;}
.show_on_print{display:block;}
blockquote{border-left:2px solid black;}
.footer_additional{display:none;}
.small_footer{margin-top:1rem;}
.page{display:table;width:100%;}
.page_selector{display:none;}
.p_thumb{height:4rem;margin-bottom:0.25rem;}
.post .p_thumb img,.post .p_thumb video{max-height:4rem;}
.sticky_title{box-shadow:none;}
.center_wide .p_thumb{display:inline-flex;height:5.8rem;width:5.8rem;margin-right:0;}
.center_wide .p_row{display:block;}
.interesting_tbody{background:none;}
.interesting_tbody img{display:none !important;}
.imd{margin-top:0.5em;margin-bottom:0.5em;line-height:0px;}
.p_row .imd{margin-top:0em;margin-bottom:0em;}
}
";
        $this->style=preg_replace('/%white%/','#231a0d',$this->style);
        $this->style=preg_replace('/%black%/','#f8ca9b',$this->style);
        $this->style=preg_replace('/%gray%/','#ac7843',$this->style);
        $this->style=preg_replace('/%graybkg%/','#39270e',$this->style);
        $this->style=preg_replace('/%lightopbkg%/','#daae8010',$this->style);
        $this->style=preg_replace('/%lighterbkg%/','#675340',$this->style);
        $this->style=preg_replace('/%focusedbkg%/','#482f0c',$this->style);
        $f = fopen('styles/main.css','w');
        fwrite($f,$this->style);
        fclose($f);
    }
    
    function GiveSafeEMail(){
        return preg_replace('/\./u','[dot]',preg_replace('/\@/u','[at]',$this->EMail));
    }
    
    function &FindImage($name, $loose=false){ if(!isset($name) || !$name){ return $this->NULL_IMAGE; }
        if(isset($this->Images[0])) foreach($this->Images as &$im){
            if($loose) { if(preg_match('/'.preg_quote($name).'/u',$im['name'])) return $im; }
            else { if($im['name']==$name) return $im; }
        }
        return $this->NULL_IMAGE;
    }
    
    function &GiveImageInHere($rand=false){
        $this->ReadImages(); $this->DetectPageType();
        $album = $this->PageType=='here'?$this->HereAlbum:$this->ExpAlbum;
        if(!isset($album) || !$album){
            if(isset($this->Images[0])) return $this->Images[0]; else return $this->NULL_IMAGE; }
        $imlist=[]; $imlist_fallback=[];
        if(isset($this->Images[0])){
            foreach($this->Images as &$im){
                if(isset($im['galleries'][0]) && in_array($album,$im['galleries']) && $this->CanShowImage($im)){
                    if(!$rand) return $im; else {
                        $imlist_fallback[] = $im;
                        if(!in_array($im['name'],$this->VisitedHere)) $imlist[] = $im;
                    }
                }
            }
        }
        if(!$rand || !isset($imlist_fallback[0])) return $this->NULL_IMAGE;
        if(sizeof($imlist)){
            $r = random_int(0, sizeof($imlist)-1);
            return $imlist[$r];
        }else{
            $r = random_int(0, sizeof($imlist_fallback)-1);
            return $imlist_fallback[$r];
        }
    }
    
    function ImageTitle($im){
        $imtitle = (isset($im)&&isset($im['title']))?$im['title']:NULL;
        if(isset($im['parent'])&&($imp = &$this->FindImage($im['parent'],true))&&isset($imp['title'])){ $imtitle=$imp['title']; }
        
        return $imtitle;
    }
    
    function ImageHasHere(&$im, $here_name){
        if(!isset($im['here'])||!isset($im['here'][0])) return false;
        foreach($im['here'] as $h) { if($h[0] == $here_name) return true; } return false;
    }
    
    function ReadImages($clear_non_exist = false){
        $path = 'images/list.md';
        if($this->DoneReadImages){ return; }
        if(!file_exists($path)){ $f = fopen($path,'w'); fflush($f); fclose($f); }
        $c = file_get_contents($path);
        if(preg_match_all('/GALLERY\s+(\S+)(.*)$/mu', $c, $ma, PREG_SET_ORDER)) foreach($ma as $m){
            $g=[]; $g['name']=$m[1];//$g['count']=0;
            if(preg_match('/FEATURED([^;]*?);/u', $m[2], $arg)){ $g['featured']=true; }
            if(preg_match('/EXPERIMENTAL([^;]*?);/u', $m[2], $arg)){ $g['experimental']=true; }
            $this->Galleries[] = $g;
        }
        if(preg_match_all('/^-\s*([^;]+)\s*?;\s*?(.*)$/mu', $c, $ma, PREG_SET_ORDER)) foreach($ma as $m){
            $name = trim($m[1]);
            $item = []; $item['file'] = 'images/'.$name; $item['name'] = $name;
            if(file_exists('images/thumb/'.$name)){$item['thumb']='images/thumb/'.$name;}else{$item['thumb']='images/'.$name;}
            if(preg_match('/REFS\s+([^;]*);/u',$m[2],$refs) && preg_match_all('/[0-9]{14}/u',$refs[1],$rs, PREG_SET_ORDER)){
                $item['refs']=[];
                foreach($rs as $r){ if(!in_array($r[0], $item['refs'])) $item['refs'][] = $r[0]; }
            }
            if(preg_match('/GAL\s+([^;]*);/u',$m[2],$gals) && preg_match_all('/(\S+)/u',$gals[1],$ga, PREG_SET_ORDER)){
                $item['galleries']=[];
                foreach($ga as $g){ if(!in_array($g[0], $item['galleries'])) $item['galleries'][] = $g[0]; }
            }
            if(preg_match('/PRODUCT\s+([^;]*);/u',$m[2],$product)){ $item['product']=$product[1]; }
            if(preg_match('/PARENT\s+([^;]*);/u',$m[2],$parent)){ $item['parent']=$parent[1]; }
            if(preg_match('/TITLE\s+([^;]*);/u',$m[2],$title)){ $item['title']=$title[1]; }
            if(preg_match('/HERE\s+([^;]*);/u',$m[2],$heres) && preg_match_all('/(\S+)-(\S+)-(\S+)/u',$heres[1],$here, PREG_SET_ORDER)){
                $item['here']=[];
                foreach($here as $h){ if(!$this->ImageHasHere($item, $h[1])) $item['here'][] = [$h[1],$h[2],$h[3]]; }
            }
            if(preg_match('/\.mp4/u',$item['name'])){
                $item['video']='video/mp4';
            }
            $this->Images[] = $item;
        }
        
        $files = array_merge([],glob('images/*.jpg'));
        $files = array_merge($files,glob('images/*.jpeg'));
        $files = array_merge($files,glob('images/*.png'));
        $files = array_merge($files,glob('images/*.gif'));
        $files = array_merge($files,glob('images/*.mp4'));
        if(isset($files[0]))foreach($files as $file) {
            if(preg_match('/[0-9]{14,}\.(jpg|jpeg|gif|png|mp4)/u', $file, $m)) {
                $name = trim($m[0]);
                if(!$this->FindImage($name)){
                    $item = []; $item['name']=$name; $item['file'] = 'images/'.$name;
                    if(file_exists('images/thumb/'.$name)){$item['thumb']='images/thumb/'.$name;}else{$item['thumb']='images/'.$name;}
                    $this->Images[] = $item;
                }
            }
        }
        if($clear_non_exist){
            if(isset($this->Images[0]) && isset($files[0])){
                foreach($this->Images as &$im){
                    if(!in_array($im['file'],$files)){
                        $im['deleted'] = 1;
                    }
                }
            }
        }
        function cmpf($a, $b){
            if ($a['name'] == $b['name']) return 0;
            return (($a['name'] > $b['name']) ? 1 : -1);
        }
        function cmpaf($a, $b){
            if ($a['name'] == $b['name']) return 0;
            return ($a['name'] > $b['name']) ? -1 : 1;
        }
        if(isset($this->Galleries[0]))usort($this->Galleries,"cmpf");
        if(isset($this->Images[0]))usort($this->Images,"cmpaf");
        $this->DoneReadImages = 1;
    }
    
    function WriteImages(){
        if(isset($this->WayBack)) return;
        $path = 'images/list.md';
        $f = fopen($path,'w');
        if(isset($this->Galleries[0]))foreach($this->Galleries as &$g){
            if(isset($g['deleted'])) continue;
            fwrite($f,'GALLERY '.$g['name']);
            if(isset($g['featured']) && $g['featured']!=false) { fwrite($f,' FEATURED;'); }
            if(isset($g['experimental']) && $g['experimental']!=false) { fwrite($f,' EXPERIMENTAL;'); }
            fwrite($f, PHP_EOL);
        }
        if(isset($this->Images[0]))foreach($this->Images as &$im){
            if(isset($im['deleted'])) continue;
            fwrite($f, "- ".$im['name'].'; ');
            if(isset($im['refs']) && isset($im['refs'][0])){ fwrite($f, 'REFS '.implode(" ",$im['refs'])."; "); }
            if(isset($im['galleries']) && isset($im['galleries'][0])){ fwrite($f, 'GAL '.implode(" ",$im['galleries'])."; "); }
            if(isset($im['product']) && $im['product']!=''){ fwrite($f, 'PRODUCT '.$im['product']."; "); }
            if(isset($im['parent']) && $im['parent']!=''){ fwrite($f, 'PARENT '.$im['parent']."; "); }
            if(isset($im['title']) && $im['title']!=''){ fwrite($f, 'TITLE '.$im['title']."; "); }
            if(isset($im['here']) && $im['here']!=''){ fwrite($f, 'HERE ');
                foreach($im['here'] as $here){ fwrite($f, implode('-',$here).' '); } fwrite($f, '; ');
            }
            fwrite($f, PHP_EOL);
        }
        fflush($f);
        fclose($f);
    }
    
    function EditImage($name, $link_gallery, $do_remove = false, $product_link=NULL, $rename=NULL, $parent=NULL){
        if(!($im = &$this->FindImage($name))) return;
        if(isset($link_gallery)){
            if($do_remove){
                if(!isset($im['galleries']) || !isset($im['galleries'][0])) return;
                foreach($im['galleries'] as $key => $g){ if ($g==$link_gallery) unset($im['galleries'][$key]); }
                $im['galleries'] = array_merge($im['galleries']);
            }else{
                if(!isset($im['galleries'])) $im['galleries']=[];
                foreach($im['galleries'] as &$g){ if ($g==$link_gallery) return; }
                $im['galleries'][]=$link_gallery;
            }
        }
        if(isset($product_link)){
            if($product_link!=''){$im['product']=$product_link;}
            else{unset($im['product']);}
        }
        if(isset($rename) && $rename!=$im['name']){
            $ext=pathinfo($im['file'],PATHINFO_EXTENSION);
            rename($im['file'], 'images/'.$rename.'.'.$ext);
            if(isset($im['thumb'])) rename($im['thumb'], 'images/thumb/'.$rename.'.'.$ext);
            $im['name'] = $rename.'.'.$ext; 
        }
        if(isset($parent) && $parent!=$im['parent']){
            if($parent==''){ unset($im['parent']); }
            else{ $im['parent'] = $parent; }
        }
        $this->NeedWriteImages = 1;
    }
    
    function RegenerateThumbnails(){
        $glob = glob('images/*.jpg');
        if(!is_dir('images/thumb')) mkdir('images/thumb');
        foreach($glob as $file) {
            $thumb_destination = 'images/thumb/'.basename($file);   
            $img = new Imagick($file); $geo=$img->getImageGeometry();
            $width=$geo['width']; $height=$geo['height'];
            $lim=400;
            $scale = $lim / min($width,$height);
            if($scale<1){
                $img->resizeImage($width*$scale,$height*$scale,imagick::FILTER_GAUSSIAN,0.7);
            }
            $img->setImageFormat('jpeg');
            $img->setImageCompressionQuality(90);
            $img->writeImage($thumb_destination);
        }
    }
    
    function CompressImage($source, $destination, $thumb_destination, $quality, $sizelim, $abs_max) {    
        $img = new Imagick($source); $geo=$img->getImageGeometry(); $img2 = clone $img;
        $width=$geo['width']; $height=$geo['height'];
        $lim=400;
        $scale = $lim / min($width,$height);
        if($scale<1){
            $img->resizeImage($width*$scale,$height*$scale,imagick::FILTER_GAUSSIAN,0.7);
        }
        $img->setImageFormat('jpeg');
        $img->setImageCompressionQuality($quality);
        $img->writeImage($thumb_destination);
        
        $scale = min( $sizelim / min($width,$height),  $abs_max / max($width,$height));
        if($scale<1){
            $img2->resizeImage($width*$scale,$height*$scale,imagick::FILTER_GAUSSIAN,0.5);
        }
        $img2->setImageFormat('jpeg');
        $img2->setImageCompressionQuality($quality);
        $img2->writeImage($destination);
    }
    
    function DoUpload(){
        if(!isset($_FILES['upload_file_name'])) return 0;
        if(!is_dir('images/thumb')) mkdir('images/thumb');
        if($_FILES['upload_file_name']['error']>0){
            echo"file upload err code ".$_FILES['upload_file_name']['error']; exit;
            return -1;
        }else{
            $ext=pathinfo($_FILES['upload_file_name']['name'],PATHINFO_EXTENSION);
            if(!in_array($ext,['jpg','jpeg','png','gif','mp4'])) return 0;
            $fp = fopen('.la_lock',"w");
            while (!flock($fp, LOCK_EX| LOCK_NB)){
                usleep(10000);
            }
            $num=date('YmdHis'); $replace=0;
            if(isset($_POST['image_replace_button']) && isset($_GET['pic']) && preg_match('/([0-9]{14,})/u',$_GET['pic'],$mim)){
                $num = $mim[1]; $replace=1;
            }
            $base = 'images/'.$num;
            $thumb = 'images/thumb/'.$num;
            if($ext=='png') $ext='jpg';
            $final_path = $base.'.'.$ext; $final_thumb = $thumb.'.'.$ext; $i=0;
            if(!$replace) while(file_exists($final_path)){
                $final_path = $base.strval($i).'.'.$ext; $final_thumb = $thumb.strval($i).'.'.$ext; $i++;
            }
            if($ext!='gif' && $ext!='mp4'){
                $compress = (isset($_GET['compress'])&&$_GET['compress']);
                $this->CompressImage($_FILES['upload_file_name']['tmp_name'], $final_path, $final_thumb, 90,
                    $compress?800:1920, $compress?1920:2560);
            }else{
                move_uploaded_file($_FILES['upload_file_name']['tmp_name'], $final_path);
            }
            flock($fp, LOCK_UN);
            fclose($fp);
            $this->ReadImages(true);
            $this->WriteImages();
            echo '<uploaded>'.pathinfo($final_path,PATHINFO_BASENAME)."</uploaded>";
            if(isset($_POST['image_replace_button'])){
                header('Location: '.$_SERVER['REQUEST_URI']);exit;
            }
            exit;
            return 1;
        }
        return 0;
    }
    
    function &GetGallery($name){
        if(isset($this->Galleries[0])) foreach($this->Galleries as &$g){
            if($g['name'] == $name) return $g;
        }
        return $this->NULL_GALLERY;
    }
    function EditGallery($name, $new_name=null, $delete=false, $do_rw=true, $set_featured=null, $set_experimental=null){
        $this->ReadImages();
        $gallery = &$this->GetGallery($name);
        if(!isset($gallery)){
            if(!isset($new_name) || preg_match('/main|trash|\s/u',$new_name))return;
            $g = []; $g['name']=$new_name;
            $this->Galleries[]=$g;
        }else{
            if(isset($new_name)) {
                if(preg_match('/main|trash|\s/u',$new_name))return;
                $gallery['name'] = $new_name;
                foreach ($this->Images as &$im){
                    if(isset($im['galleries'])&&isset($im['galleries'][0]))foreach($im['galleries'] as &$g){
                        if($g == $name){ $g = $new_name; }
                    }
                }
            }
            //if(isset($count)) $gallery['count'] = $count;
            if(isset($delete) && $delete) $gallery['deleted'] = true;
            if(isset($set_featured)) $gallery['featured'] = $set_featured;
            if(isset($set_experimental)) $gallery['experimental'] = $set_experimental;
        }
        if($do_rw) { $this->NeedWriteImages = 1; }
    }
    
    function ClearData(){
        $this->Posts = [];
        $this->Threads = [];
        $this->Images = [];
        $this->Archive = [];
        $this->ArchiveHandles = [];
    }
    
    function InsertArchivePost(&$post){
        $a = NULL;
        if(($a = &$this->GetArchiveHandle($post['id']))==$this->NULL_POST){
            $ah = []; $ah['id'] = $post['id']; $ah['list'] = [];
            $this->ArchiveHandles[] = &$ah; $a = &$ah;
        }
        $a['list'][] = $post;
        $this->Archive[] = $post;
    }
    
    function ReadArchiveFromFile($path){
        if(!file_exists($path)){
            return;
        }
        $c = file_get_contents($path);
        if(preg_match_all('/\[LAMDWIKIPOST\s+([0-9]{14})\s*;\s*([\s\S]*?)\]([\S\s]*?)(?=\[LAMDWIKIPOST|$)/u',$c,$matches,PREG_SET_ORDER)){
            foreach($matches as $m){
                $post = [];
                $post['id'] = $m[1];
                $post['content'] = trim($m[3]);
                if(preg_match('/VER\s+([0-9]{14})\s*;/u', $m[2], $n)) $post['version'] = $n[1];
                if(preg_match('/MSTDN\s+(\S+)\s*;/u', $m[2], $n)) $post['mastodon_url'] = $n[1];
                if(preg_match('/FROM\s*([^;]+);/u', $m[2], $ma)){
                    $entries = []; if(preg_match_all('/([0-9]{14})/u',$ma[1],$links,PREG_SET_ORDER)){ 
                        foreach($links as $l){ $entries[] = $l[1]; } $post['merged_from'] = $entries; } }
                if(preg_match('/HASP\s*([^;]+);/u', $m[2], $ma)){
                    $entries = []; if(preg_match_all('/([0-9]{14})/u',$ma[1],$links,PREG_SET_ORDER)){ 
                        foreach($links as $l){ $entries[] = $l[1]; } $post['hasp'] = $entries; } }
                if(preg_match('/HASI\s*([^;]+);/u', $m[2], $ma)){
                    $entries = []; if(preg_match_all('/([0-9]{14}\.(jpg|jpeg|png|gif))/u',$ma[1],$links,PREG_SET_ORDER)){ 
                        foreach($links as $l){ $entries[] = $l[1]; } $post['hasi'] = $entries; } }
                if(preg_match('/HASTAG\s*([^;]+);/u', $m[2], $ma)){
                    $entries = []; if(preg_match_all('/([0-9]{14})/u',$ma[1],$links,PREG_SET_ORDER)){ 
                        foreach($links as $l){ $entries[] = $l[1]; } $post['hastag'] = $entries; } }
                if(preg_match('/INTO\s*([0-9]{14})\s*V\s*([0-9]{14})\s*;/u', $m[2], $n)){
                    $post['merged_into'] = [trim($n[1]),trim($n[2])];
                }
                if(preg_match('/MTHREAD\s*([^;|]+)\s*\|\s*([^;]+);/u', $m[2], $ma)){
                    $entries = []; if(preg_match_all('/([0-9]{14})/u',$ma[1],$links,PREG_SET_ORDER)){ 
                        foreach($links as $l){ $entries[] = $l[1]; } $post['merged_thread'][0] = $entries; }
                    $entries = []; if(preg_match_all('/([0-9]{14})/u',$ma[2],$links,PREG_SET_ORDER)){ 
                        foreach($links as $l){ $entries[] = $l[1]; } $post['merged_thread'][1] = $entries; }
                }
                $this->InsertArchivePost($post);
            }
        }
    }
    
    function SortArchive(){
        $cmpac = function($a, $b){ if ($a['id'] == $b['id']) return 0; return (($a['id'] > $b['id']) ? 1 : -1); };
        $cmpap = function($a, $b){ if ($a['id'] == $b['id']) return ((($a['version'] > $b['version']) ? 1 : -1));
            return (($a['id'] > $b['id']) ? 1 : -1);
        };
        if(isset($this->Archive[0])){
            usort($this->Archive,$cmpap);
            $year_begin = substr($this->Archive[0]['id'],0,4);
            $this->YearBegin = ($year_begin<$this->YearBegin)?$year_begin:$this->YearBegin;
        }
        if(isset($this->ArchiveHandles[0])){
            usort($this->ArchiveHandles,$cmpac);
            foreach($this->ArchiveHandles as &$a){
                if(isset($a['list'])){ usort($a['list'],$cmpap);
                    $last_valid=NULL;
                    foreach($a['list'] as &$ver){ $ver['archive']= &$a;
                        if(isset($ver['merged_thread'])){ $ver['content']=$last_valid; }
                        else{ $last_valid = $ver['content']; } } 
                    if(isset($a['list'][0]['version'])&&$a['list'][0]['version']>$a['list'][0]['id']){ /* if early versions missing. */
                        $origin=$a['list'][0]; $origin['version']=$origin['id']; array_unshift($a['list'], $origin);
                    }
                }
                if(($p = &$this->GetPost($a['id'],true))!=NULL){
                    $p['archive'] = &$a;
                }
            }
        }
    }
    
    function ReadPostsFromFile($path){
        if(!file_exists($path)){
            $f = fopen($path,'w');
            fclose($f);
        }
        $c = file_get_contents($path);
        if(preg_match_all('/\[LAMDWIKIPOST\s+([0-9]{14})\s*;\s*([\s\S]*?)\]([\S\s]*?)(?=\[LAMDWIKIPOST|$)/u',$c,$matches,PREG_SET_ORDER)){
            foreach($matches as $m){
                $post = [];
                $post['id'] = $m[1];
                $post['real_content'] = trim($m[3]);
                $post['content'] = &$post['real_content'];
                if(preg_match('/COMMENT\s+([0-9]{14})\s*;/u', $m[2], $n)) $post['comment_to'] = $n[1];
                if(preg_match('/EMAIL\s+([^;]+)\s*;/u', $m[2], $n))    $post['email'] = $n[1];
                if(preg_match('/NAME\s+([^;]+)\s*;/u', $m[2], $n))     $post['name'] = $n[1];
                if(preg_match('/LINK\s+([^;]+)\s*;/u', $m[2], $n))     $post['link'] = $n[1];
                if(preg_match('/IP\s+([^;]+)\s*;/u', $m[2], $n))       $post['ip'] = $n[1];
                if(preg_match('/NEXT\s+([0-9]{14})\s*;/u', $m[2], $n)) $post['next'] = $n[1];
                if(preg_match('/PREV\s+([0-9]{14})\s*;/u', $m[2], $n)) $post['prev'] = $n[1];
                if(preg_match('/VER\s+([0-9]{14})\s*;/u', $m[2], $n))  $post['version'] = $n[1];
                if(preg_match('/MDEL\s*;/u', $m[2]))                   $post['mark_delete'] = True;
                if(preg_match('/MVAL\s*([^;]+);/u', $m[2], $n))        $post['mark_value'] = trim($n[1]);
                if(preg_match('/MSTDN\s+(\S+)\s*;/u', $m[2], $n))      $post['mastodon_url'] = $n[1];
                if(preg_match('/REFS\s*([^;]+);/u', $m[2], $ma)){
                    $entries = []; if(preg_match_all('/([0-9]{14})/u',$ma[1],$links,PREG_SET_ORDER)){ 
                        foreach($links as $l){ $entries[] = $l[1]; } $post['refs'] = $entries; } }
                if(preg_match('/HASP\s*([^;]+);/u', $m[2], $ma)){
                    $entries = []; if(preg_match_all('/([0-9]{14})/u',$ma[1],$links,PREG_SET_ORDER)){ 
                        foreach($links as $l){ $entries[] = $l[1]; } $post['hasp'] = $entries; } }
                if(preg_match('/HASI\s*([^;]+);/u', $m[2], $ma)){
                    $entries = []; if(preg_match_all('/([0-9]{14}\.(jpg|jpeg|png|gif))/u',$ma[1],$links,PREG_SET_ORDER)){ 
                        foreach($links as $l){ $entries[] = $l[1]; } $post['hasi'] = $entries; } }
                if(preg_match('/HASTAG\s*([^;]+);/u', $m[2], $ma)){
                    $entries = []; if(preg_match_all('/([0-9]{14})/u',$ma[1],$links,PREG_SET_ORDER)){ 
                        foreach($links as $l){ $entries[] = $l[1]; } $post['hastag'] = $entries; } }
                if(preg_match('/FROM\s*([^;]+);/u', $m[2], $ma)){
                    $entries = []; if(preg_match_all('/([0-9]{14})/u',$ma[1],$links,PREG_SET_ORDER)){ 
                        foreach($links as $l){ $entries[] = $l[1]; } $post['merged_from'] = $entries; } }
                if(preg_match('/INTO\s*([0-9]{14})\s*V\s*([0-9]{14})\s*;/u', $m[2], $n)){
                    $post['merged_into'] = [trim($n[1]),trim($n[2])]; }
                
                if(preg_match('/MTHREAD\s*([^;|]+)\s*\|\s*([^;]+);/u', $m[2], $ma)){
                    $entries = []; if(preg_match_all('/([0-9]{14})/u',$ma[1],$links,PREG_SET_ORDER)){ 
                        foreach($links as $l){ $entries[] = $l[1]; } $post['merged_thread'][0] = $entries; }
                    $entries = []; if(preg_match_all('/([0-9]{14})/u',$ma[2],$links,PREG_SET_ORDER)){ 
                        foreach($links as $l){ $entries[] = $l[1]; } $post['merged_thread'][1] = $entries; }
                }
                if(isset($post['mark_value']) && $post['mark_value']==5){
                    $post['product']=[];
                }
                /* marks add here */
                $this->Posts[] = $post;
                
                if(isset($post['comment_to']) && ($target_post = &$this->GetPost($post['comment_to'],true))){
                    if(!isset($target_post['comments']) || !isset($target_post['comments'][0])) $target_post['comments']=[];
                    $target_post['comments'][]=&$this->Posts[count($this->Posts) - 1];
                }
            }
        }
    }
    
    function SortPosts($wayback=false){
        $cmpp = function($a, $b){ if ($a['id'] == $b['id']) return 0; return (($a['id'] > $b['id']) ? 1 : -1); };
        $sortlist = &$this->Posts;
        if($wayback) $sortlist = &$this->WaybackPosts;
        if(isset($sortlist[0])){
            usort($sortlist,$cmpp);
            $this->YearEnd = substr($this->TIME_STRING,0,4);
            $this->YearBegin = substr($sortlist[0]['id'],0,4);
        }
    }
    
    function ReadPosts(){
        if ((!file_exists('la_config.md') || is_readable('la_config.md') == false) ||
            (!file_exists('la_config.php') || is_readable('la_config.php') == false) ||
            (!is_dir('posts') || is_readable('posts') == false) ||
            (!is_dir('archive') || is_readable('archive') == false) ||
            (!is_dir('images') || is_readable('images') == false) ||
            (!is_dir('styles') || is_readable('styles') == false)){
            $this->Install();
        }
        if($this->DoneReadPosts){ return; }
        $file_list = [];
        $glob = glob('posts/*');
        foreach($glob as $file) {
            if(preg_match('/[0-9]{6}\.md/', $file)) {
                $file_list[] = $file;
            }
        }
        sort($file_list, SORT_NATURAL | SORT_FLAG_CASE);
        foreach($file_list as $f) {
            $this->ReadPostsFromFile($f);
        }
        $this->SortPosts();
        $this->DetectThreads();
        $this->DoneReadPosts=1;
    }
    
    function UpdatePostRefsForWayback(){
        if(!isset($this->WayBack)) return;
        if(isset($this->Images[0])) foreach($this->Images as &$i){ unset($i['refs']); }
        if(!isset($this->WaybackPosts[0])) return;
        foreach($this->WaybackPosts as &$p){ unset($p['refs']); }
        foreach($this->WaybackPosts as &$p){
            if(isset($p['hasp']) && isset($p['hasp'][0])) foreach($p['hasp'] as $r){
                $pr = &$this->GetPost($r); if(isset($pr)){
                    if(!isset($pr['refs'])) $pr['refs']=[];
                    if(!in_array($p['id'],$pr['refs'])){ $pr['refs'][] = $p['id']; }
                }
            }
            if(isset($p['hasi']) && isset($p['hasi'][0])) foreach($p['hasi'] as $r){
                $ir = &$this->FindImage($r); if(isset($ir)){
                    if(!isset($ir['refs'])) $ir['refs']=[];
                    if(!in_array($p['id'],$ir['refs'])){ $ir['refs'][] = $p['id'];}
                }
            }
            if(isset($p['hasp']) && isset($p['hasp'][0])) foreach($p['hasp'] as $t){ if(!preg_match("/[0-9]{14}/u",$t)) continue;
                $pt=&$this->GetPost($t,false,true); if(isset($pt) && isset($pt['hastag']) && isset($pt['hastag'][0]) && in_array($t,$pt['hastag'])){
                    if(!isset($pt['refs']))$pt['refs']=[];  $pt['refs'][]=$p['id']; }
            }
        }
    }
    
    function ReadArchive(){
        if (!is_dir('archive') || is_readable('archive') == false){ $this->Install(); }
        if ($this->DoneReadArchive) return;
        $file_list = []; $glob = glob('archive/*');
        foreach($glob as $file) {
            if(preg_match('/[0-9]{6}\.md/', $file)) {
                $file_list[] = $file;
            }
        }
        sort($file_list, SORT_NATURAL | SORT_FLAG_CASE);
        foreach($file_list as $f) {
            $this->ReadArchiveFromFile($f);
        }
        $this->SortArchive();
        $this->DoneReadArchive=1;
        $this->UpdateThreadForWayback();
        $this->UpdatePostRefsForWayback();
    }
    
    function GetThreadForPost(&$post){
        if(isset($post['tid'])) return;
        $th = []; $iterp = NULL;
        $post['tid'] = &$th; $th['first'] = &$post; $th['last'] = &$post;
        if(!(isset($post['prev']) || isset($post['next']))) { $this->Threads[] = &$th; return; }
        if(isset($post['prev']))for($p = $post['prev']; $p!=NULL; $p = $iterp){
            $np = &$this->GetPost($p,true); if(!$np) { break; }//err
            $np['tid'] = &$th;
            $th['first'] = &$np;
            $iterp = isset($np['prev'])?$np['prev']:NULL;
        }
        if(isset($post['next']))for($p = $post['next']; $p!=NULL; $p = $iterp){
            $np = &$this->GetPost($p,true); if(!$np) { break; }//err 
            $np['tid'] = &$th;
            $th['last'] = &$np;
            $iterp = isset($np['next'])?$np['next']:NULL;
        }
        if(isset($th['first']['mark_value'])){
            if($th['first']['mark_value']==6)      $th['exp'] = true;
            else if($th['first']['mark_value']==7) $th['slf'] = true;
        }
        if($th['first'] == $th['last']){ unset($post['tid']); return; }
        $this->Threads[] = &$th;
    }
    
    function IdentifyThreadCategory(&$th,&$first_post){
        unset($th['categories']);unset($th['interesting']);unset($th['reversed']);
        if(preg_match('/^\s*\@(.*?)$/mu',$first_post['content'],$m)){
            $first_post['categories']=[]; if(preg_match_all('/(\S+)(\s|$)/u',$m[1],$matches,PREG_SET_ORDER)){
                foreach($matches as $ma){ $first_post['categories'][] = $ma[1]; }
            }
            if(isset($th) && $th){ $th['categories'] = &$first_post['categories']; }
        }
        if(preg_match('/\{\s*WIDE\s*\}/imu',$first_post['content'],$m)){ $first_post['wide'] = true; }
        if(preg_match('/\{\s*NO_TIME\s*\}/imu',$first_post['content'],$m)){ $first_post['no_time'] = true; }
        if(preg_match('/\{\s*HEADER\s+(.*?)\}/imu',$first_post['content'],$m)){ $first_post['header'] = trim($m[1]); }
        if(preg_match('/\{\s*FOOTER\s+(.*?)\}/imu',$first_post['content'],$m)){ $first_post['footer'] = trim($m[1]); }
        
        if(!isset($th)) return;
        if(isset($first_post['wide'])) $th['wide']=$first_post['wide'];
        if(isset($first_post['header'])) $th['header']=$first_post['header'];
        if(isset($first_post['footer'])) $th['footer']=$first_post['footer'];
        if(isset($first_post['no_time'])) $th['no_time']=$first_post['no_time'];
        if(preg_match('/\{\s*INTERESTING\s+(.*?)\}/imu',$first_post['content'],$m)){
            $th['interesting'] = []; if(preg_match_all('/(\S+)(\s|$)/u',$m[1],$matches,PREG_SET_ORDER)){
                foreach($matches as $ma){ $th['interesting'][] = $ma[1]; }
            }
        }
        if(preg_match('/\{\s*REVERSED\s*\}/imu',$first_post['content'],$m)){ $th['reversed'] = true; }
    }
    function IsInterestingPost(&$p){
        if(isset($p['tid']) && isset($p['tid']['interesting']) && isset($p['tid']['interesting'][0])) return true;
        return false;
    }
    function IsReversedThread(&$th){
        if(isset($th['reversed']) && $th['reversed']) return true;
        return false;
    }
    
    function GiveAllMergedPosts(&$po,&$arr){
        if(isset($po['merged_from'])&&isset($po['merged_from'][0])) foreach($po['merged_from'] as $pm){
            $mp=&$this->GetPost($pm); if(isset($mp)){ if(!in_array($mp['id'],$arr)) $arr[]=$mp['id']; $this->GiveAllMergedPosts($mp,$arr);}
        }
        if(isset($po['archive']) && isset($po['archive']['list'])){
            foreach($po['archive']['list'] as &$ver){
                if(isset($ver['merged_from'])&&isset($ver['merged_from'][0])) foreach($ver['merged_from'] as $pm){
                    $mp=&$this->GetPost($pm); if(isset($mp)){ if(!in_array($mp['id'],$arr)) $arr[]=$mp['id']; $this->GiveAllMergedPosts($mp,$arr); } }
            }
        }
    }
    function ThreadMakeWayback(&$th){
        if(!isset($th['arr'][0])) return;
        if(isset($this->WayBack)){
            $remlist = [];
            foreach($th['arr'] as &$pi){
                $po = &$this->GetPost($pi['id'],true);
                if(isset($po['merged_thread']) && isset($po['version']) && $po['version']>$this->WayBack){
                    $remlist = array_unique(array_merge($remlist,$po['merged_thread'][0]));
                }
                $ah = &$this->GetArchiveHandle($po['id']);
                if(isset($ah)) foreach(array_reverse($ah['list']) as &$ver){
                    if(!isset($ver['merged_thread'])) continue;
                    if((isset($ver['version']) && $ver['version'] > $this->WayBack) ||
                        (!isset($ver['version']) && $ver['id'] > $this->WayBack)){
                        $remlist = array_unique(array_merge($remlist,$ver['merged_thread'][0]));
                    }
                }
            }
            if(isset($remlist[0]) && isset($th['arr'][0])){
                foreach($remlist as $rem){ $this->GiveAllMergedPosts($this->GetPost($rem,true),$remlist); }
                foreach($th['arr'] as $key => $pr){
                    foreach($remlist as $rem){ if($pr['id'] == $rem) { unset($th['arr'][$key]); break; } } }
                $new_th = []; $new_arr = [];
                foreach($remlist as $rem){ $np=&$this->GetPost($rem); if(isset($np)){$new_arr[]=&$np;} }
                $new_th['arr'] = &$new_arr;
                $this->WaybackThreads[] = &$new_th;
                $this->ThreadMakeWayback($new_th);
            }
        }
    }
    function AddMergedPosts(&$p, &$array){
        if(isset($p['archive']) && isset($p['archive']['list'])){
            foreach($p['archive']['list'] as &$ver){
                if(isset($ver['merged_from'])&&isset($ver['merged_from'][0])) foreach($ver['merged_from'] as $po){
                    $mp=&$this->GetPost($po); if(isset($mp)){ $array[]=&$mp; $this->WaybackPosts[]=&$mp; $this->AddMergedPosts($mp, $array); } }
            }
        }
        if(isset($p['merged_from'])&&isset($p['merged_from'][0])) foreach($p['merged_from'] as $po){
            $mp=&$this->GetPost($po); if(isset($mp)){ $array[]=&$mp; $this->WaybackPosts[]=&$mp; $this->AddMergedPosts($mp, $array); } }
    }
    function FinalizeThread(&$th, $relink_posts=false, $now){
        $nextp=NULL; $arr=[]; $lasttime=NULL;
        if(!isset($th['arr'])){
            for($p = &$th['first']; $p!=$this->NULL_POST; $p = &$this->GetPost(isset($p['next'])?$p['next']:NULL,true)){
                $arr[]=&$p;
            } $th['arr'] = &$arr;
            if(isset($this->WayBack) && $this->DoneReadArchive){ $new_arr=[];
                foreach($arr as &$p){ $pa = &$this->GetPost($p['id']); if(isset($pa)) {$new_arr[]=&$pa;$this->WaybackPosts[]=&$pa;} }
                foreach($arr as &$p){ $this->AddMergedPosts($p,$new_arr); }
                $arr=&$new_arr; $th['arr']=&$new_arr; 
                $this->ThreadMakeWayback($th); }
        }
        $cmppt = function($a, $b){ if ($a['id'] == $b['id']) return 0; return (($a['id'] > $b['id']) ? 1 : -1); };
        if(isset($th['arr'][0])){ usort($th['arr'],$cmppt); } $th['count'] = sizeof($th['arr']);
        if($relink_posts){ $count = $th['count'];
            $arr = &$th['arr'];
            for($i=0; $i<$count; $i++){ if($i>0) $arr[$i]['prev'] = $arr[$i-1]['id']; if($i<$count-1) $arr[$i]['next'] = $arr[$i+1]['id']; 
                $arr[$i]['tid']=&$th;}
            $th['first'] = &$arr[0]; $th['last'] = &$arr[$count-1]; unset($arr[0]['prev']); unset($arr[$count-1]['next']);
        }
        $th['score'] = 0; if(!isset($th['first'])){ return; }
        $this->IdentifyThreadCategory($th, $th['first']);
        if(isset($this->WayBack)) $lasttime=$this->WayBack;
        if(!isset($last_time)) $lasttime = DateTime::createFromFormat('YmdHis', $th['last']['id']);
        $diff_days = ($now - date_timestamp_get($lasttime))/3600/24;
        $th['score'] = (float)$th['count']*0.2 - min($diff_days,200);
    }
    function SortThreads(){
        if (!function_exists('cmpt')) {
            function cmpt($a, $b){
                if ($a['score'] == $b['score']) return 0;
                return ($a['score'] > $b['score']) ? -1 : 1;
            }
        }
        usort($this->Threads,"cmpt");
    }
    function DetectThreads(){
        foreach($this->Posts as &$p){
            if(isset($p['tid'])) { continue; }
            $this->GetThreadForPost($p);
            if(!isset($p['tid'])) { $this->IdentifyThreadCategory($this->NULL_POST, $p); }
        }
        if(!isset($this->Threads) || !isset($this->Threads[0])) return;
        $now = date_timestamp_get(date_create());
        foreach($this->Threads as &$t){
            $this->FinalizeThread($t,false,$now);
        }
        if(isset($this->WaybackThreads[0])) foreach($this->WaybackThreads as &$th){
            $this->FinalizeThread($th,false,$now);
            $this->Threads[] = &$th;
        }
        $this->SortThreads();
    }
    function UpdateThreadForWayback(){$now = date_timestamp_get(date_create());
        if(!isset($this->WayBack) || !$this->DoneReadArchive){ return; }
        foreach($this->Threads as &$t){
            unset($t['arr']); $this->FinalizeThread($t,true,$now);
        }
        if(isset($this->WaybackThreads[0])) foreach($this->WaybackThreads as &$th){
            $this->FinalizeThread($th,true,$now);
            $this->Threads[] = &$th;
        }
        $this->SortThreads();
        $this->SortPosts(true);
        $this->UsePosts = &$this->WaybackPosts;
    }
    
    function &GetMergedPost($id){
        $this->ReadArchive();
        $ah = &$this->GetArchiveHandle($id);
        if(!isset($ah) || !isset($ah['list']) || !isset($ah['list'][0])){ return $this->NULL_POST; }
        $ver = &$ah['list'][sizeof($ah['list'])-1];
        if(!isset($ver['merged_into'])) return $this->GetPost($id);
        [$tp, $tver] = $ver['merged_into'];
        if(isset($this->WayBack)){
            if($tver <= $this->WayBack) return $this->GetMergedPost($tp);
            else return $this->GetPost($id); /* should not happen directly. */
        }
        return $this->GetMergedPost($tp);
    }
    
    function &GetPost($id, $latest_only=false, $find_merged=false){
        if(!isset($id)) return $this->NULL_POST;
        $found=&$this->NULL_POST;
        if(isset($this->Posts[0])) foreach($this->Posts as &$p){
            if($p&& $p['id'] == $id) { $found = &$p; break; }
            if($find_merged && isset($p['hastag'][0]) && in_array($id, $p['hastag'])){ $found = &$p; break; }
        }
        if($latest_only || !isset($this->WayBack)){ return $found; }
        else{
            if(isset($found)){
                if(!isset($found['archive'])&&!isset($found['archive']['list']))
                    { if($found['id'] > $this->WayBack) return $this->NULL_POST; else return $found; }
                $last_ver = &$this->NULL_POST;
                if(isset($found['archive']['list'][0])) foreach($found['archive']['list'] as &$ver){
                    if($ver['version'] > $this->WayBack) return $last_ver;
                    $last_ver = &$ver;
                }
                if(isset($found['version']) && $found['version'] <= $this->WayBack) return $found;
                else return $last_ver;
            }else{
                $ah = &$this->GetArchiveHandle($id); $last_ver = &$this->NULL_POST;
                if(isset($ah) && isset($ah['list']) && isset($ah['list'][0])) foreach($ah['list'] as &$ver){                
                    if($ver['version'] > $this->WayBack) return $last_ver;
                    $last_ver = &$ver;
                } if(!isset($last_ver['merged_into'])) return $last_ver; else{
                    [$tp, $tver] = $last_ver['merged_into'];
                    if($tver <= $this->WayBack) {
                        if($find_merged && $tp!=$id) return $this->GetPost($tp); return $this->NULL_POST; }
                    return $last_ver;
                }
            }
        }
        return $this->NULL_POST;
    }
    function &GetArchiveHandle($id){
        if(!isset($id)) return $this->NULL_POST;
        if(isset($this->ArchiveHandles[0])) foreach($this->ArchiveHandles as &$p){
            if($p && $p['id'] == $id) { return $p; }
        }
        return $this->NULL_POST;
    }
    function &GetArchive($id){
        if(!isset($id)) return $this->NULL_POST;
        if(isset($this->Archive[0])) foreach($this->Archive as &$p){
            if($p && $p['id'] == $id) { return $p; }
        }
        return $this->NULL_POST;
    }
    function &GetArchiveVersion(&$ah, $version, &$next_ver, &$last_ver){
        if(!isset($ah)) return $this->NULL_POST;
        $found = NULL; $last_verp=NULL;
        
        if(isset($ah['list'][0])) foreach($ah['list'] as &$p){
            if(isset($found)){ $next_ver = $p; $last_ver = $last_verp; return $found; }
            if($p && $p['version'] == $version) { $found = &$p; continue; }
            $last_verp = &$p;
        }
        if(isset($found)) { $next_ver=NULL; $last_ver = $last_verp; return $found; }
        return $this->NULL_POST;
    }
    
    function CacheArchiveOwnLinks(){
        if(isset($this->WayBack)) return;
        if(isset($this->Archive[0])) foreach($this->Archive as &$p){
            $this->ConvertPost($p); unset($p['hasp']); unset($p['hasi']); unset($p['hastag']);
            if(preg_match_all('/<a[^>]*href=[\'\"]\?post=([0-9]{14})[\'\"][^>]*>.*?<\/a>/u',$p['html'],$matches,PREG_SET_ORDER)){
                foreach($matches as $m){
                    if(!isset($p['hasp']))$p['hasp']=[]; if(!in_array($m[1],$p['hasp'])){ $p['hasp'][]=$m[1]; }
                }
            }
            if(preg_match_all('/\/\/([0-9]{14})/u', $p['content'],$matches,PREG_SET_ORDER)){
                foreach($matches as $m){
                    if(!isset($p['hastag']))$p['hastag']=[]; if(!in_array($m[1],$p['hastag'])){ $p['hastag'][]=$m[1]; }
                }
            }
            if(preg_match_all('/!\[([^\]]*)\]\(images\/([0-9]{14,}\.(jpg|jpeg|png|gif))\)/u', $p['content'],$matches,PREG_SET_ORDER)){
                foreach($matches as $m){
                    if(!isset($p['hasi']))$p['hasi']=[]; if(!in_array($m[2],$p['hasi'])){ $p['hasi'][]=$m[2]; }
                }
            }
        }
    }
    function WriteArchive(){
        if(isset($this->WayBack)) return;
        $cf = NULL;$opened =NULL;
        $this->SortArchive();
        $this->CacheArchiveOwnLinks();
        if(isset($this->Archive[0])) foreach($this->Archive as $p){
            $nid = substr($p['id'], 0,6);
            if($cf != $nid){
                if($opened){
                    fflush($opened);
                    fclose($opened);
                }
                $cf = $nid;
                $opened = fopen("archive/$cf.md", 'w');
            }
            $info = "[LAMDWIKIPOST {$p['id']}; ".
                    "VER {$p['version']}; ".
                    ((isset($p['merged_thread']) && isset($p['merged_thread'][0]))?
                        ("MTHREAD ".implode(" ",$p['merged_thread'][0])." | ".implode(" ",$p['merged_thread'][1]).";"):"").
                    ((isset($p['merged_from']) && isset($p['merged_from'][0]))?("FROM ".implode(" ",$p['merged_from'])."; "):"").
                    ((isset($p['merged_into']) && isset($p['merged_into'][0]))?("INTO {$p['merged_into'][0]}V{$p['merged_into'][1]}; "):"").
                    ((isset($p['hasp']) && isset($p['hasp'][0]))?("HASP ".implode(" ",$p['hasp'])."; "):"").
                    ((isset($p['hasi']) && isset($p['hasi'][0]))?("HASI ".implode(" ",$p['hasi'])."; "):"").
                    ((isset($p['hastag']) && isset($p['hastag'][0]))?("HASTAG ".implode(" ",$p['hastag'])."; "):"").
                    ']';
                    
            if(isset($p['merged_thread'])){ $p['content']=""; }
            
            fwrite($opened, $info.PHP_EOL.PHP_EOL.$p['content'].PHP_EOL.PHP_EOL);
        }
    }
    
    function WritePosts(){
        if(isset($this->WayBack)) return;
        $cf = NULL;$opened =NULL;
        $this->SortPosts();
        if(isset($this->Posts[0])) foreach($this->Posts as $p){
            $nid = substr($p['id'], 0,6);
            if($cf != $nid){
                if($opened){
                    fflush($opened);
                    fclose($opened);
                }
                $cf = $nid;
                $opened = fopen("posts/$cf.md", 'w');
            }
            $info = "[LAMDWIKIPOST {$p['id']}; ".
                    ((isset($p['version']) && $p['version'])?"VER {$p['version']}; ":"").
                    ((isset($p['merged_from']) && isset($p['merged_from'][0]))?("FROM ".implode(" ",$p['merged_from'])."; "):"").
                    ((isset($p['merged_into']) && isset($p['merged_into'][0]))?("INTO {$p['merged_into'][0]}V{$p['merged_into'][1]}; "):"").
                    ((isset($p['merged_thread']) && isset($p['merged_thread'][0]))?
                        ("MTHREAD ".implode(" ",$p['merged_thread'][0])." | ".implode(" ",$p['merged_thread'][1])."; "):"").
                    ((isset($p['comment_to']) && $p['comment_to'])?"COMMENT {$p['comment_to']}; ":"").
                    ((isset($p['email']) && $p['email'])?"EMAIL {$p['email']}; ":"").
                    ((isset($p['name']) && $p['name'])?"NAME {$p['name']}; ":"").
                    ((isset($p['link']) && $p['link'])?"LINK {$p['link']}; ":"").
                    ((isset($p['ip']) && $p['ip'])?"IP {$p['ip']}; ":"").
                    ((isset($p['mark_delete']) && $p['mark_delete'])?"MDEL; ":"").
                    ((isset($p['mark_value']) && $p['mark_value']>=0)?"MVAL {$p['mark_value']}; ":"").
                    ((isset($p['next']) && $p['next'])?"NEXT {$p['next']}; ":"").
                    ((isset($p['prev']) && $p['prev'])?"PREV {$p['prev']}; ":"").
                    ((isset($p['refs']) && isset($p['refs'][0]))?("REFS ".implode(" ",$p['refs'])."; "):"").
                    ((isset($p['hasp']) && isset($p['hasp'][0]))?("HASP ".implode(" ",$p['hasp'])."; "):"").
                    ((isset($p['hastag']) && isset($p['hastag'][0]))?("HASTAG ".implode(" ",$p['hastag'])."; "):"").
                    ((isset($p['hasi']) && isset($p['hasi'][0]))?("HASI ".implode(" ",$p['hasi'])."; "):"").
                    ((isset($p['mastodon_url']) && isset($p['mastodon_url']))?("MSTDN ".$p['mastodon_url']."; "):"").
                    ']';
                    
            fwrite($opened, $info.PHP_EOL.PHP_EOL.$p['real_content'].PHP_EOL.PHP_EOL);
        }
    }
    
    function CachePostLinks(){
        if(isset($this->WayBack)) return;
        if(isset($this->Posts) && isset($this->Posts[0]))foreach ($this->Posts as &$post){
            $this->ConvertPost($post);
            unset($post['refs']);unset($post['hasp']);unset($post['hasi']);unset($post['hastag']);
            //discard lost old version
            //if(!isset($post['archive']) && $this->DoneReadArchive && isset($post['version'])){ unset($post['version']); }
        }else return;
        if(isset($this->Images) && isset($this->Images[0])) foreach ($this->Images as &$im){
            unset($im['refs']); unset($im['here']); unset($im['title']);
        }
        foreach ($this->Posts as &$post){
            if(preg_match_all('/<a[^>]*href=[\'\"]\?post=([0-9]{14})[\'\"][^>]*>.*?<\/a>/u',$post['html'],$matches,PREG_SET_ORDER)){
                foreach($matches as $m){
                    $ref = &$this->GetPost($m[1],true);
                    if($ref!=NULL){
                        if(!isset($ref['refs']))$ref['refs']=[]; if(!in_array($post['id'],$ref['refs']))$ref['refs'][]=$post['id'];
                    }
                    if(!isset($post['hasp']))$post['hasp']=[]; if(!in_array($m[1],$post['hasp']))$post['hasp'][]=$m[1];
                }
            }
            if(preg_match_all('/\/\/([0-9]{14})/u', $post['content'],$matches,PREG_SET_ORDER)){
                foreach($matches as $m){
                    if(!isset($post['hastag']))$post['hastag']=[]; if(!in_array($m[1],$post['hastag']))$post['hastag'][]=$m[1];
                }
            }
            if(preg_match_all('/!\[([^\]]*)\]\(images\/([0-9]{14,}\.(jpg|jpeg|png|gif|mp4))\)/u', $post['content'],$matches,PREG_SET_ORDER)){
                foreach($matches as $m){  
                    if(($im = &$this->FindImage($m[2]))!=NULL){
                        if(!isset($im['refs']))$im['refs']=[]; if(!in_array($post['id'], $im['refs']))$im['refs'][] = $post['id'];
                    }
                    if(!isset($post['hasi']))$post['hasi']=[]; if(!in_array($m[2],$post['hasi']))$post['hasi'][]=$m[2];
                }
            }
            if(preg_match("/(\!\[.*?\]\(\s*images\/([0-9]{14,}\.(jpg|jpeg|png|gif|mp4))\))(\R*(\R(-|\*).+){1,})/u",
                $post['content'],$matches)){
                $use_img_name = $matches[2];
                if(($im = &$this->FindImage($use_img_name))!=NULL){
                    if(preg_match_all("/(-|\*)\s+here\s*:\s*([0-9]{1,3})\s*,\s*([0-9]{1,3})\s+images\/([0-9]{14,}\.(jpg|jpeg|png|gif|mp4))/u",
                        $matches[4],$mah,PREG_SET_ORDER)){
                        foreach($mah as $ma){
                            if(!isset($im['here']))$im['here']=[]; if(!$this->ImageHasHere($item, $ma[4])){
                                $here = [$ma[4], $ma[2], $ma[3]]; $im['here'][] = $here; }
                        }
                    }
                }
            }
        }foreach($this->Posts as &$post){
            if(isset($post['hasp']) && isset($post['hasp'][0])) foreach($post['hasp'] as $t){ if(!preg_match("/[0-9]{14}/u",$t)) continue;
                $pt=&$this->GetPost($t,false,true); if(isset($pt) && isset($pt['hastag']) && isset($pt['hastag'][0]) && in_array($t,$pt['hastag'])){
                    if(!isset($pt['refs']))$pt['refs']=[]; $pt['refs'][]=$post['id']; }
            }
        }
        if(isset($this->Images) && isset($this->Images[0])) foreach ($this->Images as &$im){
            $min_id = '99999999999999';
            if(isset($im['refs']) && isset($im['refs'][0])) foreach ($im['refs'] as &$ref){
                $r = &$this->GetPost($ref,true); if(isset($r)) $title = $this->GetPostTitle($r,true,false);
                if($title && $ref<$min_id) { $im['title'] = preg_replace('/;/u',' ',trim($title)); $min_id=$r['id']; }
            }
        }
    }
    
    function CreatePostAnchor(&$post){
        $that=&$this;
        $post['html'] = preg_replace_callback('/<h([0-9])>(.+?)(?=<\/h[0-9]>)/u', function ($ma) use ($that){
                $id = '_heading_'.sizeof($this->Anchors);
                $entry=[(int)$ma[1], $id, $ma[2]];
                $that->Anchors[] = $entry;
                return "<h${ma[1]} id='${id}'>${ma[2]}";
            }, $post['html']);
    }
    
    function RenamePost(&$post, $rename){
        foreach($this->Posts as &$p){
            if($p['id']==$rename && $p!==$post) { return; /* don't overwrite */ }
        }
        foreach($this->Posts as &$p){
            if(isset($p['prev']) && $p['prev']==$post['id']) { $p['prev']=$rename; }
            if(isset($p['next']) && $p['next']==$post['id']) { $p['next']=$rename; }
        }
        $post['id'] = $rename;
    }
    
    function DetachPost(&$th,&$post){
        $p0=$p1=NULL;foreach($th['arr'] as $a) echo $a['id']." "; echo '&nbsp;&nbsp;&nbsp;-'.$post['id']."<br />"; 
        if(isset($post['prev'])){$p0=&$this->GetPost($post['prev'],true);} if(isset($post['next'])){$p1=&$this->GetPost($post['next'],true);}
        if(isset($p0)){ $p0['next']=$p1['id']; }else{ $th['first']=$p1['id']; }
        if(isset($p1)){ $p1['prev'] =$p0['id']; }else{ $th['last']=$p0['id']; }
        foreach($this->Posts as $key => $p){ if ($p==$post) unset($this->Posts[$key]); }
        unset($th['arr']); $now = date_timestamp_get(date_create());
        $this->FinalizeThread($th, true, $now);
    }
    
    function PushPostVersion(&$post, $optime_id, $mode=1, $info=NULL){
        $a = &$this->GetArchiveHandle($post['id']);
        $ap = []; $ap['id'] = $post['id']; $ap['content'] = $post['real_content'];
        $ap['version'] = isset($post['version'])?$post['version']:$ap['id'];
        if(isset($post['merged_from'])) { $ap['merged_from'] = $post['merged_from']; unset($post['merged_from']); }
        if(isset($post['merged_into'])) { $ap['merged_into'] = $post['merged_into']; unset($post['merged_into']); }
        if(isset($post['merged_thread'])) { $ap['merged_thread'] = $post['merged_thread']; unset($post['merged_thread']); }
        $post['version'] = $optime_id;
        if($mode==1){
            $this->InsertArchivePost($ap);
        }else if($mode==2){
            $post['merged_from'] = $info;
            $this->InsertArchivePost($ap);
        }else if($mode==3){
            $ap['merged_into'] = $info;
            $this->InsertArchivePost($ap);
            if(isset($post['tid']) && $post['tid']['first']!=$post['tid']['last'] && $post!=$post['tid']['first']){
                $this->DetachPost($post['tid'], $post);
            }
        }else if($mode==4){
            $post['merged_thread'] = $info;
            $this->InsertArchivePost($ap);
        }
    }
    
    function &MergeThreads($post_id, $post_into_id){
        $this->ReadPosts();
        $po = &$this->GetPost($post_id, true); $pt = &$this->GetPost($post_into_id, true);
        if (!isset($po) || !isset($pt) || !isset($po['tid']) || !isset($pt['tid']) ||
            !isset($po['tid']['arr'][0]) || !isset($pt['tid']['arr'][0])){ return $this->NULL_POST; }
        $po = &$po['tid']['arr'][0]; $pt = &$pt['tid']['arr'][0];
        $th = &$po['tid']; $tt = &$pt['tid']; $info=[[],[]];
        if($po['id'] < $pt['id']){ $temp = &$th; $th=&$tt; $tt=&$temp; $temp = &$po; $po=&$pt; $pt=&$temp; }
        
        foreach($th['arr'] as &$p){ $info[0][]=$p['id']; }
        foreach($tt['arr'] as &$p){ $info[1][]=$p['id']; }
        $this->EditPost($pt['id'], NULL, NULL, NULL, false, NULL, NULL, 4, $info,NULL);
        
        $arr_combined = array_merge($tt['arr'],$th['arr']);
        
        $cmppmt = function($a, $b){ if ($a['id'] == $b['id']) return 0; return (($a['id'] > $b['id']) ? 1 : -1); };
        if(isset($arr_combined[0])){ usort($arr_combined,$cmppmt); } $tt['arr']=&$arr_combined;
        $tt['count'] = sizeof($tt['arr']);
        $this->FinalizeThread($tt, true, $this->TIME_STRING);
        
        $first_post = &$this->NULL_POST;
        if(isset($tt['arr'][0])) $first_post= &$tt['arr'][0];
        $this->NeedWritePosts=1;
        $this->NeedWriteArchive=1;
        return $first_post;
    }
    
    function &MergePosts($ids_string){
        $ids = explode(" ", $ids_string);
        $combined_content = ""; $first_id=NULL;
        $this->ReadPosts(); $child_list=[];
        foreach($ids as $id){
            if(!preg_match('/[0-9]{14}/u',$id) || $this->GetPost($id,true)==$this->NULL_POST) continue;
            if(!isset($first_id)) { $first_id = $id; } else { $combined_content.=PHP_EOL.PHP_EOL."//".$id.PHP_EOL; $child_list[]=$id; }
            $combined_content.=$this->EditPost($id, NULL, NULL, NULL, true, NULL, NULL, false, NULL,NULL);
        }
        if(!isset($child_list[0])) return $this->NULL_POST;
        $first_post = &$this->EditPost($first_id, $combined_content, NULL, NULL, false, NULL, NULL, 2, $child_list,NULL);
        foreach($child_list as $id){
            $this->EditPost($id, NULL, NULL, NULL, false, NULL, NULL, 3, [$first_id,$this->TIME_STRING],NULL);
        }
        $this->NeedWritePosts=1;
        $this->NeedWriteArchive=1;
        return $first_post;
    }
    
    /* push_version: 1 edit   2 post merged from ids list   3 post end into id-ver */
    function &EditPost($id_if_edit, $content, $mark_delete, $reply_to,
                       $get_original_only=false, $mark_value=NULL, $rename=NULL,
                       $push_version=false, $version_info=NULL, $mastodon_url=NULL){
        $this->ReadImages();
        $this->ReadPosts();
        if(isset($push_version) && $push_version){
            $this->ReadArchive();
        }
        $p_success = NULL;
        if(isset($id_if_edit)){
            $post = &$this->GetPost($id_if_edit,true);
            if($post===$this->NULL_POST) return $this->NULL_POST;
            if($get_original_only){
                return $post['real_content'];
            }
            if(isset($push_version) && $push_version){
                $this->PushPostVersion($post,$this->TIME_STRING,$push_version,$version_info);
            }
            if(isset($content)) {$post['real_content'] = $content; $post['content'] = &$post['real_content'];}
            if(isset($mark_delete)) $post['mark_delete'] = $mark_delete;
            if(isset($mark_value)) $post['mark_value'] = $mark_value;
            if(isset($rename) && preg_match('/^[0-9]{14}$/u',$rename)) $this->RenamePost($post,$rename);
            if(isset($mastodon_url)) { if($mastodon_url!='') $post['mastodon_url']=$mastodon_url; else unset($post['mastodon_url']); }
            $p_success = &$post;
        }else{
            if(!isset($content)) return $this->NULL_POST;
            $id = date('YmdHis');
            if($this->GetPost($id,true)!== $this->NULL_POST) return $this->NULL_POST;
            $post = []; $post['id'] = $id;
            $post['real_content'] = $content; $post['content'] = &$post['real_content'];
            if(isset($reply_to) && ($rep = &$this->GetPost($reply_to,true))!== $this->NULL_POST){
                while($rep !== $this->NULL_POST && isset($rep['next']) && $rep['next']){ $rep = &$this->GetPost($rep['next'],true); }
                if($rep !== $this->NULL_POST){ $rep['next'] = $id; $post['prev'] = $rep['id']; }
            }
            $this->Posts[] = $post;
            $p_success = &$this->Posts[count($this->Posts) - 1];
        }
        $this->NeedWritePosts=1;
        $this->NeedWriteImages=1;
        if(isset($push_version) && $push_version){
            $this->NeedWriteArchive=1;
        }
        return $p_success;
    }
    
    function &EditComment($id_if_edit, $comment_to_id, $content, $email, $name, $link=NULL, $ip=NULL){
        $this->ReadPosts();
        $p_success = NULL;
        if(isset($id_if_edit)){
            $post = &$this->GetPost($id_if_edit,true);
            if($post===$this->NULL_POST || !isset($post['comment_to'])) return $this->NULL_POST;
            if(isset($content)) $post['content'] = $content;
            if(isset($comment_to_id)) $post['comment_to'] = $comment_to_id;
            if(isset($email)) $post['email'] = $email; if(isset($name)) $post['name'] = $name; if(isset($link)) $post['link'] = $link;
            if(isset($ip)) $post['ip'] = $ip;
            $p_success = &$post;
        }else{
            if(!isset($content) || !isset($comment_to_id)) return $this->NULL_POST;
            $id = date('YmdHis');
            if($this->GetPost($id,true)!== $this->NULL_POST) return $this->NULL_POST;
            if(!($to_post=$this->GetPost($comment_to_id,true))) return $this->NULL_POST;
            $post = []; $post['id'] = $id; $post['content'] = $content; $post['comment_to'] = $comment_to_id;
            if(isset($email)) $post['email'] = $email; if(isset($name)) $post['name'] = $name; if(isset($link)) $post['link'] = $link;
            if(isset($ip)) $post['ip'] = $ip;
            $this->Posts[] = $post;
            $p_success = &$this->Posts[count($this->Posts) - 1];
        }
        $this->NeedWritePosts=1;
        return $p_success;
    }
    
    function InsertReplacementSymbols($MarkdownContent, &$post_for_it_is_here){
        $replacement = preg_replace('/<!--[\s\S]*-->/U',"",$MarkdownContent);
        $replacement = preg_replace_callback("/(```|`)([^`]*)(?1)/U",
                    function($matches){
                        $rep = preg_replace('/->/','-@>',$matches[0]);
                        $rep = preg_replace('/=>/','=@>',$rep);
                        $rep = preg_replace('/<=/','<@=',$rep);
                        $rep = preg_replace('/<-/','<@-',$rep);
                        $rep = preg_replace('/\R([+]{3,})\R/',PHP_EOL.'@$1'.PHP_EOL,$rep);
                        $rep = preg_replace('/\[-/','[@-',$rep);
                        $rep = preg_replace('/\{/','{@',$rep);
                        $rep = preg_replace('/(en|zh|any)\|(en|zh|any)/','$1@|$2',$rep);
                        return $rep;
                    },
                    $replacement);
        $replacement = preg_replace("/<[-]+>/","↔",$replacement);
        $replacement = preg_replace("/([^-])->/","$1→",$replacement);
        $replacement = preg_replace("/<-([^-])/","←$1",$replacement);
        $replacement = preg_replace("/([^-])[-]+->/","$1⟶",$replacement);
        $replacement = preg_replace("/<-[-]+([^-])/","⟵$1",$replacement);
        $replacement = preg_replace("/<[=]+>/","⇔",$replacement);
        $replacement = preg_replace("/[=]+>/","⇒",$replacement);
        $replacement = preg_replace("/<[=]+/","⇐",$replacement);
        $replacement = preg_replace("/\R([+]{3,})\R/","<div class='page_break'></div>",$replacement);
        $replacement = preg_replace("/\[-(.*)-\]/U","<span class='text_highlight'>$1</span>",$replacement);
        $replacement = preg_replace("/{支付宝(\s+[^}]*?)?}/u","<span class='special_alipay'>支付宝$1</span>",$replacement);
        $replacement = preg_replace("/{PayPal(\s+[^}]*?)?}/ui",
            "<span class='special_paypal'>Pay<span class='special_paypal_inner'>Pal</span>$1</span>",$replacement);
        $replacement = preg_replace("/\/\/([0-9]{14})/imu","<p class='wscroll' id='$1'>".$this->T("链接位置")."</p>",$replacement);
        $replacement = preg_replace_callback("/(```|`)([^`]*)(?1)/U",
                    function($matches){
                        $rep = preg_replace('/-@>/','->',$matches[0]);
                        $rep = preg_replace('/<@-/','<-',$rep);
                        $rep = preg_replace('/=@>/','=>',$rep);
                        $rep = preg_replace('/<@=/','<=',$rep);
                        $rep = preg_replace('/\R@([+]{3,})\R/',PHP_EOL.'$1'.PHP_EOL,$rep);
                        $rep = preg_replace('/\[@-/','[-',$rep);
                        $rep = preg_replace('/\{@/','{',$rep);
                        $rep = preg_replace('/(en|zh|any)@\|(en|zh|any)/','$1|$2',$rep);
                        return $rep;
                    }, $replacement);
        $replacement = preg_replace_callback("/(\!\[.*?\]\(\s*images\/([0-9]{14,}\.(jpg|jpeg|png|gif))\))(\R*(\R.+){1,})/u",
                    function($matches) use (&$post_for_it_is_here){
                        $rep = preg_replace("/(-|\*)\s+here\s*:\s*([0-9]{1,3})\s*,\s*([0-9]{1,3})\s+images\/([0-9]{14,}\.(jpg|jpeg|png|gif))/u","", $matches[4]);
                        return $matches[1].$rep;
                    }, $replacement);
        return $replacement;
    }
    
    function CanShowPost(&$p){
        if(!isset($p) || isset($p['comment_to'])) return false;
        $is_mark_exp = (isset($p['tid'])&&isset($p['tid']['exp'])&&$p['tid']['exp']) || (isset($p['mark_value'])&&$p['mark_value']==6);
        $is_mark_slf = (isset($p['tid'])&&isset($p['tid']['slf'])&&$p['tid']['slf']) || (isset($p['mark_value'])&&$p['mark_value']==7);
        if($is_mark_slf && !$this->LoggedIn){ return false; }
        if(isset($this->WayBack)){
            if(isset($p['version'])) { if ($p['version']>$this->WayBack) return false; }
            else if ($p['id']>$this->WayBack) return false;
        }
        if(!$this->InExperimentalMode){
            if(!$this->LoggedIn){
                if($is_mark_exp) return false;
                return true;
            }
            return true;
        }else{
            if($is_mark_exp) return true;
            return false;
        }
    }
    
    function SkipProduct(&$p){
        if($this->LoggedIn) return false;
        return (isset($p['mark_value']) && $p['mark_value']==5);
    }
    
    function GetRedirect($args=NULL){
        $str = 'index.php?';
        if(isset($args['post'])) $str.='&post='.$args['post'];
            else if(isset($_GET['post'])) $str.='&post='.$_GET['post'];
        if(isset($args['gallery'])) $str.='&gallery='.$args['gallery'];
            else if(isset($_GET['gallery'])) $str.='&gallery='.$_GET['gallery'];
        if(isset($args['pic'])) $str.='&pic='.$args['pic'];
            else if(isset($_GET['pic'])) $str.='&pic='.$_GET['pic'];
        if(isset($args['settings'])) $str.='&settings='.$args['settings'];
            else if(isset($_GET['settings'])) $str.='&settings='.$_GET['settings'];
        if(isset($args['extras'])) $str.='&extras='.$args['extras'];
            else if(isset($_GET['extras'])) $str.='&extras='.$_GET['extras'];
        if(isset($args['category'])) $str.='&category='.$args['category'];
            else if(isset($_GET['category'])) $str.='&category='.$_GET['category'];
        if(isset($args['history'])) $str.='&history='.$args['history'];
            else if(isset($_GET['history'])) $str.='&history='.$_GET['history'];
        if(isset($args['version'])) $str.='&version='.$args['version'];
            else if(isset($_GET['version'])) $str.='&version='.$_GET['version'];
        if(isset($args['search'])) $str.='&search='.$args['search'];
            else if(isset($_GET['search'])) $str.='&search='.$_GET['search'];
        if(isset($args['here'])) $str.='&here='.$args['here'];
            else if(isset($_GET['here'])) $str.='&here='.$_GET['here'];
        return $str;
    }
    
    function WriteAsNecessary(){
        if(!$this->LoggedIn || isset($this->WayBack)){return;}
        if($this->NeedWritePosts){ $this->CachePostLinks(); $this->WritePosts(); }
        if($this->NeedWriteImages){ $this->WriteImages(); }
        if($this->NeedWriteArchive){ $this->WriteArchive(); }
    }
    
    function APubEnsureWebfinger($name, $host){
        if(!is_dir('.well-known')) mkdir('.well-known');
        if(!is_dir('.well-known/webfinger')) mkdir('.well-known/webfinger');
        $f = fopen('.well-known/webfinger/index.php',"w");
        $without_protocol = parse_url($host, PHP_URL_HOST);
        $finger = ["subject"=>"acct:".$name.'@'.$without_protocol,
                   "links"=>[["rel"=>"self", "type"=>"application/activity+json", "href"=>$host."?apub_actor=1"]]];
        fwrite($f, "<?php header('Content-Type: application/json'); echo '".json_encode($finger, JSON_UNESCAPED_SLASHES)."'; ?>"); fclose($f);
        
        if(!file_exists('.well-known/apub_public_key.pem') || !file_exists('.well-known/apub_private_key.php')){
            $res = openssl_pkey_new();
            $this->APubPublicKey = openssl_pkey_get_details($res)['key'];
            openssl_pkey_export($res, $this->APubPrivateKey);
            $f = fopen('.well-known/apub_public_key.pem',"w");
            fwrite($f, $this->APubPublicKey); fclose($f);
            $f = fopen('.well-known/apub_private_key.php',"w");
            fwrite($f, "<?php exit; ?>".PHP_EOL.PHP_EOL.$this->APubPrivateKey); fclose($f);
        }
    }
    
    function APubEnsureInfo(){
        if(!isset($this->APubID) || !isset($this->HostURL)) return;
        if(!file_exists('.well-known/apub_public_key.pem')){$this->APubEnsureWebfinger($this->APubID, $this->HostURL);}
        $pk = file_get_contents('.well-known/apub_public_key.pem'); //$pk = preg_replace('/\n/u','\\n',$pk);
        $actor = ["@context"=>["https://www.w3.org/ns/activitystreams","https://w3id.org/security/v1"],
                  "id"=> $this->HostURL."?apub_actor=1",
                  "type"=> "Person",
                  "name"=> $this->DisplayName,
                  "url"=> $this->HostURL,
                  "summary"=> "Lazy... No summary",
                  "preferredUsername"=> $this->APubID,
                  "inbox"=> $this->HostURL."?apub_inbox=1",
                  "outbox"=> $this->HostURL."?apub_outbox=1",
                  "publicKey"=> ["id"=> $this->HostURL."?apub_actor=1#main-key",
                                 "owner"=> $this->HostURL."?apub_actor=1",
                                 "publicKeyPem"=>$pk]];
        $this->APubActor = json_encode($actor, JSON_UNESCAPED_SLASHES);
    }
    
    function APubMakeOutbox(){
        $this->ReadPosts();$this->ReadImages();
        $obj = ["@context"=>"https://www.w3.org/ns/activitystreams",
                "id"=>$this->HostURL."?apub_outbox=1",
                "type"=>"OrderedCollection"];
        $items=[]; $i=0;
        foreach(array_reverse($this->Posts) as &$p){
            $this->ConvertPost($p);
            $text = strip_tags(preg_replace('/<\/(p|blockquote|h[0-9])>/u',"\n\n",$p['html']));
            $time = DateTime::createFromFormat('YmdHis', $p['id'], new DateTimeZone('+0800'));
            $ob = ["@context"=>"https://www.w3.org/ns/activitystreams",
                   "type"=> "Create",
                   "id"=> $this->HostURL."?post=".$p['id']."?apub_object=1",
                   "published"=> $time->format('Y-m-d\TH:i:s\Z'),
                   //"to"=> ["https://chatty.example/ben/"],
                   "actor"=> $this->HostURL."?apub_actor=1",
                   "to"=> ["https://www.w3.org/ns/activitystreams#Public"],
                   "object"=> ["type"=> "Note",
                               "id"=> $this->HostURL."?post=".$p['id'],
                               "published"=> $time->format('Y-m-d\TH:i:s\Z'),
                               "attributedTo"=> $this->HostURL."?apub_actor=1",
                               "to"=> ["https://www.w3.org/ns/activitystreams#Public"],
                               "content"=> $text]];
            $items[] = $ob;
            $i++; if($i>20) break;
        }
        $obj['orderedItems'] = $items; $obj["totalItems"] = sizeof($items);
        return json_encode($obj, JSON_UNESCAPED_SLASHES);
    }
    
    function MakeRSS(){
        $this->ReadPosts();$this->ReadImages();
        $posts = []; if(isset($this->UsePosts[0])){
            $posts = array_reverse($this->UsePosts);
            $last_updated = $this->StandardTime($posts[0]['id']);
        }else{ $last_updated= $this->StandardTime("20000101000000"); }
        $author = "<author><name>".$this->DisplayName."</name><email>".$this->EMail."</email><uri>".$this->HostURL."</uri></author>";
        $all_content="<?xml version='1.0' encoding='UTF-8'?><feed xmlns='http://www.w3.org/2005/Atom'>".
        "<id>".$this->HostURL."/?rss</id>".
        "<updated>".$last_updated."</updated>".
        "<title>".$this->T($this->Title)."</title>".
        "<link href='".$this->HostURL."/?rss' rel='self'/>".
        "<link href='".$this->HostURL."' />".$author;
        $i=0;
        if(isset($posts[0])) foreach($posts as &$p){
            if($i>100) break;
            if(!$this->CanShowPost($p) || $this->SkipProduct($p)) continue;
            if(isset($p['tid'])){ /* Should always be set. */
                $th = &$p['tid']; if($p['tid']['count']==0) continue; }
            
            if(in_array($p['id'],
                [$this->SpecialPinned,$this->SpecialFooter,$this->SpecialFooter2,$this->SpecialNavigation])) continue;
            if(isset($p['tid'])){ if(isset($p['tid']['displayed'])) continue; $p['tid']['displayed'] = True; }
            
            
            $is_thread = isset($p['tid']['count'])&&($p['tid']['count']>1);
            $is_reversed=false;
            $content="<entry>"; $title = NULL; 
            if($is_thread){
                $use_arr = $th['arr']; $is_reversed=$this->IsReversedThread($th); $hinted=false; if($is_reversed){
                $use_arr=array_reverse($th['arr']); $fp=array_pop($use_arr); array_unshift($use_arr,$fp); }
                foreach($use_arr as &$po){
                    $this->ConvertPost($po);
                    if(!isset($title)){$title = $this->GetPostTitle($po, false, false);
                        $content.="<id>".$this->HostURL."?post=".$po['id']."</id>";
                        $content.="<title>".$title."</title><link rel='alternate' href='".$this->HostURL."/?post=".$po['id']."' />";
                        $content.="<published>".$this->StandardTime($po['id'])."</published>";
                        $content.="<updated>".$this->StandardTime(isset($po['tid']['last']['version'])?
                            $po['tid']['last']['version']:$po['tid']['last']['id'])."</updated>".$author;
                        $content.="<content type='html'>"; }
                    $content.= htmlspecialchars($po['html']);
                    //if(isset($po['images'])&&isset($po['images'][0])) foreach($po['images'] as $im){ $content.=htmlspecialchars($im); }
                }
                $content.=$this->ReadableTime($p['tid']['first']['id'])." - ".
                    $this->ReadableTime(isset($p['tid']['last']['version'])?
                        $p['tid']['last']['version']:$p['tid']['last']['id']);
                $content.="</content>";
            }else{
                $this->ConvertPost($p);
                if(!isset($title)){$title = $this->GetPostTitle($p, false, false);
                    $content.="<id>".$this->HostURL."?post=".$p['id']."</id>";
                    $content.="<title>".$title."</title><link rel='alternate' href='".$this->HostURL."/?post=".$p['id']."' />";
                    $content.="<published>".$this->StandardTime($p['id'])."</published>";
                    $content.="<updated>".$this->StandardTime(isset($p['tid']['last']['version'])?
                        $p['tid']['last']['version']:$p['tid']['last']['id'])."</updated>".$author;}
                $content.= "<content type='html'>".htmlspecialchars($p['html']);
                //if(isset($p['images'])&&isset($p['images'][0])) foreach($p['images'] as $im){ $content.=htmlspecialchars($im); }
                $content.=$this->ReadableTime($p['id'])." - ".
                    $this->ReadableTime(isset($p['version'])?$p['version']:$p['id']);
                $content.="</content>";
            } 
            $i++;
            $content.="</entry>";
            $all_content.=$content;
        }
        $all_content.="</feed>";
        header( "Content-type: text/xml");
        echo $all_content;
    }
    
    function MakeHereButtons($im, $insert_here){
        if(isset($im['here'])&&isset($im['here'][0])){
            $size=getimagesize($im['file']); $aspect=$size[0]/$size[1];
            echo ($insert_here?"<here>":"")."<div class='here_buttons_inner' style='aspect-ratio:".$aspect.";'>";
            foreach($im['here'] as $here){
                if($insert_here){
                    echo "<a href='?show_image=".$here[0]."' class='here_btn' style='left:".$here[1]."%;top:".$here[2]."%;'".
                        "onclick='event.preventDefault();event.stopPropagation();ShowBigImage(\"".$here[0]."\",1);'></a>";
                }else{
                    echo "<a href='?here=images/".$here[0]."' class='here_btn' style='left:".$here[1]."%;top:".$here[2]."%;'></a>";
                }
            }
            echo "</div>".($insert_here?"</here>":"");
        }
    }
    
    function ProcessRequest(&$message=NULL, &$redirect=NULL){
        if(isset($_GET['gallery']) && $_GET['gallery']=='default'){
            $redirect = "index.php?gallery=".(isset($this->DefaultGallery)&&$this->DefaultGallery!=''?$this->DefaultGallery:"main");
            return 0;
        }
        if(isset($_GET['set_language'])){
            setcookie('la_language',$_GET['set_language'],time()+3600*24*7); $_COOKIE['la_language'] = $_GET['set_language'];
            $redirect=$this->GetRedirect(); return 0;
        }
        if(isset($_GET['rss'])){
            if(in_array($_GET['rss'],['en','zh'])){$this->LanguageAppendix=$_GET['rss'];}
            $this->MakeRSS(); exit;
        }
        if(isset($_GET['toggle_font'])){ $use_font='local';
            if(!isset($_COOKIE['la_font']) || $_COOKIE['la_font']!='remote') $use_font='remote';
            setcookie('la_font',$use_font,time()+3600*24*7); $_COOKIE['la_font'] = $use_font;
            $redirect=$this->GetRedirect(); return 0;
        }
        if(isset($_GET['set_wayback'])){
            if($_GET['set_wayback']!='false'){
                $wayback= date('YmdHis');
                if($_GET['set_wayback']=='post'){
                    if(isset($_POST['wayback_year'])) $wayback=substr_replace($wayback,str_pad($_POST['wayback_year'],4,"0",STR_PAD_LEFT),0,4);
                    if(isset($_POST['wayback_month'])) $wayback=substr_replace($wayback,str_pad($_POST['wayback_month'],2,"0",STR_PAD_LEFT),4,2);
                    if(isset($_POST['wayback_day'])) $wayback=substr_replace($wayback,str_pad($_POST['wayback_day'],2,"0",STR_PAD_LEFT),6,2);
                    if(isset($_POST['wayback_hour'])) $wayback=substr_replace($wayback,str_pad($_POST['wayback_hour'],2,"0",STR_PAD_LEFT),8,2);
                    if(isset($_POST['wayback_minute'])) $wayback=substr_replace($wayback,str_pad($_POST['wayback_minute'],2,"0",STR_PAD_LEFT),10,2);
                }else{ if(preg_match('/[0-9]{14}/u', $_GET['set_wayback'])) $wayback = $_GET['set_wayback']; }
                setcookie('la_wayback',$wayback); $_COOKIE['la_wayback'] = $wayback;
            }else{
                setcookie('la_wayback', null, -1); unset($_COOKIE['la_wayback']);
            }
            $redirect=$this->GetRedirect(); return 0;
        }
        if(isset($_GET['post'])){ $this->CurrentPostID = $_GET['post']; $this->TagID = $this->CurrentPostID; }
        if(isset($_GET['here'])){ $this->HereID = $_GET['here']; }
        if(isset($_GET['offset'])){ $this->CurrentOffset = $_GET['offset']; }
        if(isset($_GET['part'])){
            if($_GET['part'] == 'hot') $this->ExtraScripts.="ShowLeftSide();";
            else if ($_GET['part'] == 'recent') $this->ExtraScripts.="ShowCenterSide();";
        }
        if(isset($_GET['post'])){
            $this->ExtraScripts.="window.addEventListener('load', (event) => {ScrollToPost('".$_GET['post']."');});";
        }
        if(isset($_GET['random_here'])){
            $this->DoIdentifyExperimental(); $this->DetectPageType();
            $im = &$this->GiveImageInHere(true); if(isset($im)){ $redirect='?here=images/'.$im['name']; return 0; }
        }
        if(isset($_POST['search_content'])){ $redirect='index.php?search='.$_POST['search_content'];return 0; }
        if(isset($_GET['image_info']) || isset($_GET['show_image'])){
            $m=isset($_GET['image_info'])?$_GET['image_info']:$_GET['show_image'];
            $direct_return = !isset($_GET['show_image']);
            $this->ReadImages(); $this->ReadPosts();
            $this->SwitchWayBackMode(); if(isset($this->WayBack)){$this->ReadArchive();}
            $im = &$this->FindImage($m);
            if($im==NULL || !isset($im['refs']) || !isset($im['refs'][0])){ echo "not_found"; exit; }
            if($direct_return){
                echo "<ref>".sizeof($im['refs'])."</ref>";
                echo "<insert><ul>";
            }else{ ob_start(); }
            foreach(array_reverse($im['refs']) as $ref){
                $p = $this->GetPost($ref);
                if(!$p || !$this->CanShowPost($p)) continue;
                $this->MakeSinglePost($p, false, true, "post_preview", true, false, false, false, true);"</li>";
            }
            if($direct_return){
                echo "</ul></insert>"; 
                $this->MakeHereButtons($im, true);
            }else{
                $str = ob_get_clean();
                $this->MakePageBegin();
                $side = "";
                if(isset($im['refs'])&&isset($im['refs'][0])){
                    $side.="<span class='small'>".$this->T('该图片出现在')." ".sizeof($im['refs'])." ".$this->T('个帖子中')."</span>";
                }else{$side.="<span class='smaller gray'>".$this->T('该图片未被引用')."</span>";}
                $static_image = isset($im['video'])?NULL:$im['file'];
                $static_video = isset($im['video'])?$im['file']:NULL;
                $this->MakeImageOverlay($side."<ul>".$str."</ul>",$static_image,$static_video);
                $this->MakePageEnd();
            }
            exit;
        }
        if(isset($_GET['confirm_enter']) && $_GET['confirm_enter']!=false){
            setcookie('la_experimental','confirmed'); $_COOKIE['la_experimental'] = $_GET['confirmed'];
            $redirect='index.php'.(isset($_GET['post'])?'?post='.$_GET['post']:"");return 0;
        }
        if(isset($_GET['apub_actor'])){
            $this->APubEnsureInfo(); header('Content-Type: application/json'); header('Cache-Control no-store, no-cache, must-revalidate');
            echo $this->APubActor; exit;
        }
        if(isset($_GET['apub_outbox'])){
            $this->APubEnsureInfo(); header('Content-Type: application/json'); header('Cache-Control no-store, no-cache, must-revalidate');
            echo $this->APubMakeOutbox(); exit;
        }
        if($this->LoggedIn){
            $this->DoUpload();
            
            if(isset($_POST['settings_button'])){
                if(isset($_POST['settings_title'])) $this->Title=$_POST['settings_title'];
                if(isset($_POST['settings_short_title'])) $this->ShortTitle=$_POST['settings_short_title'];
                if(isset($_POST['settings_display_name'])) $this->DisplayName=$_POST['settings_display_name'];
                if(isset($_POST['settings_email'])) $this->EMail=$_POST['settings_email'];
                if(isset($_POST['settings_special_navigation'])) $this->SpecialNavigation=$_POST['settings_special_navigation'];
                if(isset($_POST['settings_special_footer'])) $this->SpecialFooter=$_POST['settings_special_footer'];
                if(isset($_POST['settings_special_footer2'])) $this->SpecialFooter2=$_POST['settings_special_footer2'];
                if(isset($_POST['settings_special_pinned'])) $this->SpecialPinned=$_POST['settings_special_pinned'];
                if(isset($_POST['settings_default_gallery'])) $this->DefaultGallery=$_POST['settings_default_gallery'];
                if(isset($_POST['settings_selfauth_path'])) $this->SelfAuthPath=$_POST['settings_selfauth_path'];
                if(isset($_POST['settings_enable_comments'])) $this->CommentEnabled=True; else $this->CommentEnabled=False;
                if(isset($_POST['settings_here_host'])) $this->HereHost=$_POST['settings_here_host'];
                if(isset($_POST['settings_here_title'])) $this->HereTitle=$_POST['settings_here_title'];
                if(isset($_POST['settings_here_short_title'])) $this->HereShortTitle=$_POST['settings_here_short_title'];
                if(isset($_POST['settings_here_album'])) $this->HereAlbum=$_POST['settings_here_album'];
                if(isset($_POST['settings_here_navigation'])) $this->HereNavigation=$_POST['settings_here_navigation'];
                if(isset($_POST['settings_here_footer'])) $this->HereFooter=$_POST['settings_here_footer'];
                if(isset($_POST['settings_exp_host'])) $this->ExpHost=$_POST['settings_exp_host'];
                if(isset($_POST['settings_exp_title'])) $this->ExpTitle=$_POST['settings_exp_title'];
                if(isset($_POST['settings_exp_short_title'])) $this->ExpShortTitle=$_POST['settings_exp_short_title'];
                if(isset($_POST['settings_exp_caution'])) $this->ExpCaution=$_POST['settings_exp_caution'];
                if(isset($_POST['settings_exp_album'])) $this->ExpAlbum=$_POST['settings_exp_album'];
                if(isset($_POST['settings_exp_navigation'])) $this->ExpNavigation=$_POST['settings_exp_navigation'];
                if(isset($_POST['settings_exp_footer'])) $this->ExpFooter=$_POST['settings_exp_footer'];
                if(isset($_POST['settings_mastodon_token'])) $this->MastodonToken=$_POST['settings_mastodon_token'];
                if(isset($_POST['settings_mastodon_url'])) $this->MastodonURL=$_POST['settings_mastodon_url'];
                if(isset($_POST['settings_mastodon_lang'])) $this->MastodonPreferredLang=$_POST['settings_mastodon_lang'];
                if(isset($_POST['settings_host_url'])) $this->HostURL=$_POST['settings_host_url'];
                if(isset($_POST['settings_apub_id']) && isset($this->HostURL)) { 
                    $this->APubID=$_POST['settings_apub_id'];
                    $this->APubEnsureWebfinger($this->APubID,$this->HostURL);
                }
                if(isset($_POST['settings_old_password'])&&password_verify($_POST['settings_old_password'], $this->Password)){
                    if(isset($_POST['settings_id'])) $this->Admin=$_POST['settings_id'];
                    if(isset($_POST['settings_new_password']) && isset($_POST['settings_new_password_redo']) && 
                        $_POST['settings_new_password'] = $_POST['settings_new_password_redo'])
                        {$this->Password=password_hash($_POST['settings_new_password'], PASSWORD_DEFAULT);}
                    $redirect=$_SERVER['REQUEST_URI'];
                    $this->DoLogout();
                }
                $this->WriteConfig();
                return 0;
            }
            if(isset($_POST['settings_save_redirect'])){
                if(isset($_POST['settings_redirect'])){
                    $this->BuildRedirectConfig($_POST['settings_redirect']);
                }
                $this->WriteConfig();
                $redirect = 'index.php?extras=true'; return 0;
            }
            if(isset($_POST['settings_save_translation'])){
                if(isset($_POST['settings_translation'])){
                    $f = fopen("custom_translations.md", "w"); fwrite($f,$_POST['settings_translation']); fflush($f); fclose($f);
                }
                $redirect = 'index.php?extras=true'; return 0;
            }
            if(isset($_GET['post'])){
                if(isset($_GET['post_original'])){
                    echo $this->EditPost($_GET['post'],NULL,false,NULL,true,NULL,NULL,false,NULL,NULL);
                    exit;
                }
            }
            if(isset($_GET['mark_delete']) && isset($_GET['target'])){
                $this->EditPost($_GET['target'],NULL,$_GET['mark_delete']=='true',NULL,false,NULL,NULL,false,NULL,NULL);
                if(isset($_GET['post'])) $redirect='?post='.$_GET['target']; else $redirect=$this->GetRedirect();
                return 0;
            }
            if(isset($_GET['set_mark']) && isset($_GET['target'])){
                $this->EditPost($_GET['target'],NULL,NULL,NULL,NULL,$_GET['set_mark'],NULL,false,NULL,NULL);
                if(isset($_GET['post'])) $redirect='?post='.$_GET['target']; else $redirect=$this->GetRedirect();
                return 0;
            }
            if(isset($_POST['post_button']) && isset($_POST['post_content'])){
                $c = $_POST['post_content'];
                if('有什么想说的' == $c){ return 0;}
                if(preg_match('/\[LAMDWIKIPOST/u',$c))
                    { $message='Can\'t use character sequence"[LAMDWIKIPOST" anywhere in the post...'; return 1; }
                $reply_to = (isset($_POST['post_reply_to'])&&$_POST['post_reply_to']!="")?$_POST['post_reply_to']:NULL;
                $edit_id = (isset($_POST['post_edit_target'])&&$_POST['post_edit_target']!="")?$_POST['post_edit_target']:NULL;
                $push_history = (isset($edit_id) && isset($_POST['post_record_edit']));
                if(($edited = $this->EditPost($edit_id, $c, NULL, $reply_to,NULL,NULL,NULL,$push_history,NULL,NULL))!=NULL){
                    $redirect='?post='.$edited['id']; return 0;
                };
            }
            if(isset($_POST['post_rename_confirm']) && isset($_POST['post_rename_name']) && isset($_GET['rename_post'])){
                if(($edited =$this->EditPost($_GET['rename_post'],NULL,NULL,NULL,NULL,NULL,$_POST['post_rename_name'],false,NULL,NULL))!=NULL){
                    $redirect='?post='.$edited['id']; return 0;
                };
            }
            if(isset($_POST['post_mastodon_confirm']) && isset($_POST['post_mastodon_url']) && isset($_GET['mastodon_post'])){
                if(($edited =$this->EditPost(
                    $_GET['mastodon_post'],NULL,NULL,NULL,NULL,NULL,$_POST['post_rename_name'],false,NULL,$_POST['post_mastodon_url']))!=NULL){
                    $redirect='?post='.$edited['id']; return 0;
                };
            }
            if(isset($_GET['mastodon_send_post']) && preg_match('/([0-9]{14})/u',$_GET['mastodon_send_post'],$m)){
                $this->ReadImages(); $this->ReadPosts(); $post = &$this->GetPost($m[1]); $err=$this->T("未知错误");
                if(isset($post)){ 
                    if($mastodon_url = $this->MastodonSendPost($post, $err)){
                        $this->NeedWritePosts = 1; $this->WritePosts(); // for mastodon url
                        echo "SUCCESS ".$mastodon_url;
                    }else{ echo $err; } 
                } exit;
            }
            if(isset($_GET['merge_threads'])&&$_GET['merge_threads']!=""){
                if(preg_match('/([0-9]{14})\s+([0-9]{14})/u',$_GET['merge_threads'],$m)){
                    if(($pe = &$this->MergeThreads($m[1],$m[2]))!=$this->NULL_POST){ $redirect='?post='.$pe['id']; return 0; }
                }
            }
            if(isset($_GET['merge_posts'])&&$_GET['merge_posts']!=""){
                if(($pe = &$this->MergePosts($_GET['merge_posts']))!=$this->NULL_POST){
                    $redirect='?post='.$pe['id']; return 0;
                }
            }
            if ($this->CommentEnabled && isset($_POST['comment_confirm']) && (isset($_GET['comment_to']))
                && isset($_POST['comment_box']) && isset($_POST['comment_email']) && isset($_POST['comment_name'])){
                $c = $_POST['comment_box'];
                if(preg_match('/\[LAMDWIKIPOST/u',$c))
                    { $message='Can\'t use character sequence"[LAMDWIKIPOST" anywhere in the post...'; return 1; }
                $comment_to = ($_GET['comment_to']!="")?$_GET['comment_to']:NULL;
                if(($edited = $this->EditComment(NULL,
                        $_GET['comment_to'], $c, $_POST['comment_email'], $_POST['comment_name'], 
                        isset($_POST['comment_link'])?$_POST['comment_link']:NULL,
                        $_SERVER['REMOTE_ADDR']))!=NULL){
                    $redirect='?post='.$_GET['post'];
                    return 0;
                };
            }
            if(isset($_POST['gallery_edit_confirm']) && isset($_POST['gallery_edit_new_name']) && $_POST['gallery_edit_new_name']!=''){
                $old_name = isset($_POST['gallery_edit_old_name'])?$_POST['gallery_edit_old_name']:"";
                $new_name = $_POST['gallery_edit_new_name'];
                if($old_name!=''){
                    $this->EditGallery($old_name, $new_name, false, true, null, null);
                    $redirect='?gallery='.$new_name;
                }else{
                    $this->EditGallery(null, $new_name, false, true, null);
                    if(isset($_GET['gallery'])) $redirect='?gallery='.$_GET['gallery']; else $redirect='index.php';
                }
                return 0;
            }
            if(isset($_GET['gallery_edit_delete'])&&$_GET['gallery_edit_delete']!=null){
                $this->EditGallery($_GET['gallery_edit_delete'], null, true, true, null, null);
                if(isset($_GET['gallery'])) $redirect='?gallery=main'; else $redirect='index.php';
                return 0;
            }
            if(isset($_POST['gallery_move_ops'])&&isset($_POST['gallery_move_ops'])){
                if(preg_match('/^(REM|ADD)\s+(\S+)\s+(.*)$/u', $_POST['gallery_move_ops'], $ma)){
                    $this->ReadImages();
                    if(preg_match_all('/(\S+)/u', $ma[3], $files, PREG_SET_ORDER)) foreach($files as $name){
                        $this->EditImage($name[1], $ma[2], ($ma[1]=='REM'), NULL, NULL, NULL);
                    }
                }
                if(isset($_GET['gallery'])) $redirect='?gallery='.$_GET['gallery']; else $redirect='index.php';
                return 0;
            }
            if(isset($_GET['gallery_set_featured'])&&isset($_GET['value'])){
                $this->EditGallery($_GET['gallery_set_featured'], null, false, true, $_GET['value']!='false', null);
                if(isset($_GET['gallery'])) $redirect='?gallery='.$_GET['gallery']; else $redirect='index.php';
                return 0;
            }
            if(isset($_GET['gallery_set_experimental'])&&isset($_GET['value'])){
                $this->EditGallery($_GET['gallery_set_experimental'], null, false, true, null, $_GET['value']!='false');
                if(isset($_GET['gallery'])) $redirect='?gallery='.$_GET['gallery']; else $redirect='index.php';
                return 0;
            }
            if(isset($_GET['image_list'])&&$_GET['image_list']!=""){
                $this->ReadImages();
                $gallery = $_GET['image_list'];
                foreach($this->Images as $im){
                    if($gallery=='main'){ echo "[".$im['name'].",".(isset($im['thumb'])?$im['thumb']:$im['name'])."]"; continue; }
                    if(isset($im['galleries']) && isset($im['galleries'][0]) && in_array($gallery,$im['galleries'])) {
                        echo "[".$im['name'].",".(isset($im['thumb'])?$im['thumb']:$im['name'])."]"; }
                }
                exit;
            }
            $do_image_redirect = 0;
            if (isset($_POST['image_button']) && isset($_GET['pic']) &&
                preg_match('/([0-9]{14,}\.(jpg|png|jpeg|gif|mp4))/u',$_GET['pic'],$ma)){
                $this->ReadImages(); $pic = $ma[1]; $picext=$ma[2];
                if(isset($_POST['image_ops_product_link'])){
                    $this->EditImage($pic, NULL, false, $_POST['image_ops_product_link'], NULL, NULL);
                    $redirect=$_SERVER['REQUEST_URI']; $do_image_redirect = 1;
                }
                if(isset($_POST['image_edit_new_name']) && preg_match('/\s*([0-9]{14,})\s*/u',$_POST['image_edit_new_name'],$man)){
                    $this->EditImage($pic, NULL, false, NULL, $man[1], NULL);
                    $redirect=$this->GetRedirect(['pic'=>'images/'.$man[1].'.'.$picext]);
                    $do_image_redirect = 1;
                }
                if(isset($_POST['image_parent'])){ $parent="";
                    if(preg_match('/\s*([0-9]{14,})\s*/u',$_POST['image_parent'],$man)){ $parent = $man[1]; }
                    $this->EditImage($pic, NULL, false, NULL, NULL, $parent);
                    $redirect=$_SERVER['REQUEST_URI']; $do_image_redirect = 1;
                }
                $this->NeedWriteImages = 1;
                if($do_image_redirect) return 0;
            }
            if(isset($_GET['rewrite_styles'])){
                $this->WriteStyles();
                $redirect='?extras=true'; return 0;
            }
            if(isset($_GET['regenerate_thumbnails'])){
                $this->RegenerateThumbnails();
                $redirect='?extras=true'; return 0;
            }
            if(isset($_GET['clear_all_logins'])){
                $this->LoginTokens=[];
                $this->WriteTokens();
            }
            
        }
        return 0;
    }
    
    function PostProcessHTML($html,&$added_images=null,$do_product_info=false, &$product_info=null, &$orig_src_list=null, &$end_wide=null, &$print_wide=null){
        if(!$this->LoggedIn){
            $html = preg_replace("/(<a[^>]*href=[\'\"])(.*?)([\'\"][^>]*)>(\#.*?<\/a>)/u","",$html);
        }
        $html = preg_replace("/(<a[^>]*href=[\'\"])([0-9]{14})([\'\"][^>]*)>(.*?<\/a>)/u","$1?post=$2$3 onclick='ShowWaitingBar()'>$4",$html);
        $html = preg_replace("/(<a[^>]*href=[\'\"])\@(.*?)([\'\"][^>]*)>(.*?<\/a>)/u","$1?category=$2$3 onclick='ShowWaitingBar()'>$4",$html);
        $html = preg_replace("/(<a[^>]*href=[\'\"])((.*?:\/\/).*?)([\'\"][^>]*)(>)(.*?)(<\/a>)/u",
                             "$1$2$4 target='_blank'$5$6<sup>↗</sup>$7",$html);
        $html = preg_replace("/<p>\s*\@.*?<\/p>/mu","",$html);
        $html = preg_replace("/\{\s*(INTERESTING|HEADER|FOOTER)\s+(.*?)\}/imu","",$html);
        $html = preg_replace("/\{\s*(REVERSED|NO_TIME)\s*\}/imu","",$html);
        $html = preg_replace("/\{\s*WIDE\s*\}/imu","",$html,-1,$rep_count); if($rep_count){$print_wide=true;}
        $images = []; $images_noclick = []; $images_orig_src_list = [];
        $html = preg_replace_callback(
                    "/(<p>\s*)?(<img([^>]*)src=[\'\"])(images\/([0-9]{14,}\.(jpg|png|jpeg|gif|mp4)))([\'\"][^>]*)\/>(\s*<\/p>)?/u",
                    function($m) use (&$images,&$images_noclick,&$images_orig_src_list) {
                        $orig_src = $src = $m[5]; $keep = false; $original = false;
                        if (preg_match('/alt=[\'\"].*keep_inline.*[\'\"]/u',$m[3]) ||
                            preg_match('/alt=[\'\"].*keep_inline.*[\'\"]/u',$m[7])) { $keep=true; }
                        if ($keep && preg_match('/alt=[\'\"].*original.*[\'\"]/u',$m[3]) ||
                                     preg_match('/alt=[\'\"].*original.*[\'\"]/u',$m[7])) { $original=true; }
                        if(($im = &$this->FindImage($m[5]))!=NULL && isset($im['thumb'])){ 
                            $src = $im['thumb']; $orig_src=$im['file'];
                        }if($im == NULL){ $im = &$this->NULL_IMAGE_DUMMY; }
                        $media_start = $m[2];
                        $media_end = $m[7];
                        $dataset = " data-imgsrc='".$m[5]."'".
                                    (isset($im['product'])?" data-product='".$im['product']."'":"").
                                    (isset($im['parent'])?" data-parent='".$im['parent']."'":"").
                                    ($original?" class='original_img'":"");
                        if(isset($im['video']) && isset($im['video'])!=''){
                            $media_start = "<video controls".$dataset."><source src='"; $media_end="' type='".$im['video']."'></video";
                            $src = $orig_src; $dataset="";
                        }
                        if($this->InHereMode){
                            $click = "<div class='imd'>"."<a href='?here=".$im['file']."' class='original_img'>".
                                        $media_start.$orig_src.$media_end."></a></div>";
                            return $click;
                        }else{
                            $click ="<div class='imd'><a href='?show_image={$im['name']}' target='_blank' onclick='event.preventDefault();'>".
                                $media_start.($original?$orig_src:$src).$media_end.$dataset."></a></div>";
                            $images_noclick[]=$media_start.$src.$media_end.">"; $images_orig_src_list[]=$orig_src;
                            $ret = "";
                            if($keep) { $ret = $click; }
                            else { $images[] = $click; }
                            if(isset($m[1])&&isset($m[8])&&$m[1]&&$m[8]) return $ret;
                            else return ((isset($m[1])&&$m[1]?$m[1]:"").$ret.(isset($m[8])&&$m[8]?$m[8]:""));
                        }
                    },$html,-1,$count);
        $html = preg_replace('/<p>\s*<\/p>/u',"", $html); if($html==""){$html="<p>&nbsp;</p>";}
        if(sizeof($images)){
            if(sizeof($images)==1){$html.= $images[0]; }
            else{
                $html.="<div class='p_row'>";
                foreach($images as $img){
                    $html.="<div class='p_thumb'>".$img."</div>";
                }
                $html.="<div class='p_thumb' style='flex-grow:10000;box-shadow:none;height:0;'></div></div>";
            }
        }
        if(sizeof($images_noclick)){ $added_images = $images_noclick; }
        if(sizeof($images_orig_src_list)) { $orig_src_list = $images_orig_src_list; }
        if($do_product_info){
            $html = preg_replace_callback("/\{PRICE\s+([^]]+?)\}/u",
                    function($m) use (&$product_info) { $product_info['price']=$m[1];return ""; },$html);
            $html = preg_replace_callback("/\{SHORT\s+([^]]+?)\}/u",
                    function($m) use (&$product_info) { $product_info['short']=$m[1];return ""; },$html);
            $html = preg_replace('/<p>\s*<\/p>/u',"", $html);
            if (preg_match('/<h[1-5]>(.+?)<\/h/u',$html,$title)){ $product_info['title']=$title[1]; }
            else { $product_info['title'] = $this->T('商品'); }
            if(!isset($product_info['price'])) $product_info['price']=$this->T('未设置价格');
            $html = preg_replace_callback("/\{PURCHASE\s+([^]]+?)\}/u",function($m) use (&$product_info) {
                    return "<a class='purchase_button' href=\"mailto:".$this->T($this->DisplayName)."<".$this->EMail.">?subject=".
                            $this->T('购买').' '.$product_info['title'].
                            "&body=".$this->T('你好！我想购买').$product_info['title'].urlencode(PHP_EOL.PHP_EOL).
                            $this->FullURL().urlencode(PHP_EOL.PHP_EOL)."\">".$m[1]."</a>"; },$html);
        }
        if(preg_match("/(<\/div>|<\/table>|<\/video>)\s*$/u",$html)){$end_wide=true;}
        return $html;
    }
    
    function ConvertPost(&$post){
        if(!isset($post['html'])){
            $info=[];
            $post['html'] = $this->TranslatePostParts($this->PostProcessHTML($this->ChoosePartsByLanguage($this->PDE->text($this->InsertReplacementSymbols($post['content'], $post)),true),
                                                   $post['images'],
                                                   isset($post['product']), $info,
                                                   $post['original_images'],
                                                   $post['end_wide'],
                                                   $post['wide']));
            if(isset($post['product'])) $post['product']=$info;
        }
    }
    function GetPostTitle(&$post, $h1_only=false, $add_unamed=true){
        if(!isset($post['title'])){
            if($h1_only){
                if(preg_match('/^#\s+(.*?)$/mu',$post['content'],$m)){return $m[1];}
                return NULL;
            }
            if(preg_match('/^#{1,6}\s+(.*?)$/mu',$post['content'],$m)){$post['title']=$m[1];}
            else{ $post['title'] = $add_unamed?$this->T('未命名'):""; 
                if(preg_match('/(.*)$/mu',$post['content'],$m)){
                    $post['title'].=($add_unamed?' (':'').strip_tags($this->PDE->text($m[1])).($add_unamed?')':''); } 
            }
        }
        return $this->ChoosePartsByLanguage($post['title']);
    }
    
    function DetectPageType(){
        if ($this->InExperimentalMode) $this->PageType='experimental';
        else if($this->InHereMode) $this->PageType='here';
        else if(isset($_GET['history'])) $this->PageType='history';
        else if(isset($_GET['extras'])) $this->PageType='extras';
        else if(isset($_GET['settings'])) $this->PageType='settings';
        else if(isset($_GET['gallery'])) $this->PageType='gallery';
        else if(isset($_GET['search'])) $this->PageType='search';
        else if(isset($_GET['category'])) $this->PageType='category';
        else if(isset($this->CurrentPostID)) $this->PageType = "post";
        else if(isset($_GET['comments'])) $this->PageType='comments';
        else $this->PageType = "main";
        
        $visited_here = $this->InExperimentalMode?"visited_experimental":"visited_here";
        if(!isset($_SESSION[$visited_here])){ $_SESSION[$visited_here] = []; }
        $this->VisitedHere = $_SESSION[$visited_here];
    }
    
    function MakePageBegin(){ ?>
        <!DOCTYPE html><html lang='zh-Hans-CN'>
        <head>
        <meta charset='utf-8'>
        <meta content="width=device-width, initial-scale=1" name="viewport">
        <title><?=$this->InExperimentalMode?$this->T($this->ExpTitle):
            ($this->InHereMode?$this->T($this->HereTitle):$this->T($this->Title))?></title>
        <?php if($this->UseRemoteFont){ ?><style>
@font-face{font-family: "Noto Serif CJK SC";src:url("fonts/NotoSerifSC-Regular.otf") format("opentype");font-weight:normal;}
@font-face{font-family: "Noto Serif CJK SC";src:url("fonts/NotoSerifSC-Bold.otf") format("opentype");font-weight:bold;}
</style><?php } ?>
        <link href='styles/main.css' rel='stylesheet' type="text/css">
        <link rel="alternate" type="application/rss+xml" title="<?=$this->T($this->Title);?>" href="?rss=<?=$this->LanguageAppendix;?>" />
        <?php if(isset($this->SelfAuthPath)&&$this->SelfAuthPath!=""){ ?>
            <link rel="authorization_endpoint" href="<?=$this->SelfAuthPath?>" /><?php } ?>
        </head>
        <div class='page'>
    <?php }
    function MakeHeader(&$p){ 
        $this->MakePageBegin() ?>
        <script type='text/javascript'>
            function toggle_mobile_show(a){a.classList.toggle('hidden_on_mobile')}
            function ShowWaitingBar(){
                wait = document.querySelector("#waiting_bar");
                wait.style.display='';
            }
            function HideWaitingBar(){
                wait = document.querySelector("#waiting_bar");
                wait.style.display='none';
            }
        <?php if(!$this->InHereMode){ ?>
            function la_auto_grow(element){
                s=window.scrollY;element.style.height="30px";element.style.height=(element.scrollHeight)+"px";window.scroll(0, s);
            }
            var scroll_l=0,scroll_c=0;in_center=1;
            function ShowLeftSide(){
                scroll_c = window.scrollY;
                document.getElementById('div_left').classList.remove('hidden_on_mobile');
                document.getElementById('div_center').classList.add('hidden_on_mobile');
                window.scroll(0, scroll_l); in_center=0;
            }
            function ShowCenterSide(){
                scroll_l = window.scrollY;
                document.getElementById('div_center').classList.remove('hidden_on_mobile');
                document.getElementById('div_left').classList.add('hidden_on_mobile');
                window.scroll(0, scroll_c); in_center=1;
            }
            function ShowBackdrop(alpha=0.5){
                b = document.getElementById('backdrop');
                b.style="background-color:rgba(0,0,0,"+alpha+");";
                b.style.animation='backdrop_fade_in 0.3s forwards';
                b.style.display = 'block';
            }
            function HideBackdrop(){
                b = document.getElementById('backdrop');
                b.style.animation='backdrop_fade_out 0.3s forwards';
                setTimeout(function(e1){e1.style.display='none';}, 300, b);
            }
            function ShowRightSide(big,elem_to_clone){
                p = document.getElementById('pop_right');
                if(elem_to_clone){
                    c = elem_to_clone.cloneNode(true);
                    c.id='side_bar_cloned';c.className="";c.style="";
                    p.querySelector('._content').appendChild(c);
                }
                p.classList.remove('pop_right','pop_right_big');
                if(big) {p.classList.add('pop_right_big');p.style.animation='pop_slide_in_big 0.3s forwards';}
                else {p.classList.add('pop_right');p.style.animation='pop_slide_in 0.3s forwards';}
                p.style.display='block';
                ShowBackdrop(0.5);
            }
            function HideRightSide(){
                p = document.getElementById('pop_right');
                if(side=p.querySelector('#side_bar_cloned')){
                    side.remove();
                }
                put = document.querySelector("#_uploader");
                if(p.classList.contains('pop_right')){p.style.animation='pop_slide_out 0.3s forwards';}
                else{p.style.animation='pop_slide_out_big 0.3s forwards';}
                setTimeout(function(e1, e2){e1.style.display='none';if(e2)e2.style.display='none';}, 300, p, put);
                HideBackdrop();
            }
            function ToggleLeftSide(){if(in_center)ShowLeftSide();else ShowCenterSide();}
            
            function CssNumberID(id){ return "#\\3"+id.substr(0,1)+" "+id.substr(1); }
            function ScrollToPost(id){
                post=null;
                if(!(post = document.querySelector("[data-post-id='"+id+"']"))){
                    if(!(post = document.querySelector(CssNumberID(id)))) return; } 
                post.scrollIntoView({ behavior: 'smooth', block: 'start'});
            }
        <?php } ?>
        </script>
        <header<?=$this->InHereMode?' class="exp_h_f"':''?>><div>
        <?php if($this->InHereMode){
            $this->MakeExpNavButtons($p);
        }else{
            $this->MakeNavButtons($p);
            if(isset($p['tid']['header'])){ ?><div style='float:right' class='show_on_print'><?=$p['tid']['header'];?></div><?php }
            echo "<hr />";
        } ?>
        <?php if(isset($this->WayBack)){
            $time=(isset($_COOKIE['la_wayback']) && preg_match('/[0-9]{14}/u',$_COOKIE['la_wayback']))?$_COOKIE['la_wayback']:date('YmdHis');
            $year=substr($time,0,4);$month=substr($time,4,2);$day=substr($time,6,2);
            $hour=substr($time,8,2);$minute=substr($time,10,2);?> <div class='text_highlight'>
                &nbsp;<b><span onclick='document.getElementById("wayback_config").classList.toggle("hidden_on_mobile")'>
                <div class='wayback_close' onclick='event.stopPropagation()'><a href='<?=$this->GetRedirect()?>&set_wayback=false'>
                    <span class='hidden_on_mobile'><?=$this->T('退出')?></span>×&nbsp;</a></div>
                <?=$this->T('正以过去的日期浏览')?></b><div id='wayback_config' class='wayback_expand hidden_on_mobile'>
                    <select id="wayback_year" name="wayback_year" form="wayback_form"><?php for($y=$this->YearBegin;$y<=$this->YearEnd;$y++){ ?>
                        <option value="<?=$y?>"<?=$y==$year?" selected":""?>><?=$y?></option><?php } ?></select>
                    <br class='hidden_on_desktop' />
                    <select id="wayback_month" name="wayback_month" form="wayback_form"><?php for($y=1;$y<=12;$y++){ ?>
                        <option value="<?=$y?>"<?=$y==$month?" selected":""?>><?=$y?></option><?php } ?></select>
                    <select id="wayback_day" name="wayback_day" form="wayback_form"><?php for($y=1;$y<=31;$y++){ ?>
                        <option value="<?=$y?>"<?=$y==$day?" selected":""?>><?=$y?></option><?php } ?></select>
                    <br class='hidden_on_desktop' />
                    <select id="wayback_hour" name="wayback_hour" form="wayback_form"><?php for($y=0;$y<=23;$y++){ ?>
                        <option value="<?=$y?>"<?=$y==$hour?" selected":""?>><?=$y?></option><?php } ?></select>:
                    <select id="wayback_minute" name="wayback_minute" form="wayback_form"><?php for($y=0;$y<=59;$y++){ ?>
                        <option value="<?=$y?>"<?=$y==$minute?" selected":""?>><?=$y?></option><?php } ?></select>
                    <br class='hidden_on_desktop' />
                    <form action="<?=$this->GetRedirect()?>&set_wayback=post" method="post" style='display:none;' id='wayback_form'></form>
                    <input type='submit' style='font-weight:normal;border-bottom:none;' class='button clean_a' form='wayback_form'
                        id='wayback_see' name='wayback_see' value="<?=$this->T('查看')?> →">
            </div></div><?php } ?>
        </div></header>
        <div id='waiting_bar' style='display:none;'></div>
        <script>ShowWaitingBar();window.addEventListener('load',(event) =>{HideWaitingBar();}); </script>
    <?php if(!$this->InHereMode){ ?>
        <div id='post_menu' style='display:none;' class='pop_menu clean_a'>
            <ul>
            <li><span id='_time_hook' class='smaller'>时间</span>&nbsp;&nbsp;<a href='javascript:HidePopMenu();'>×</a></li>
            <li><a id='share_copy'>⎘<?=$this->T('复制链接')?></a></li>
            <hr />
            <?php if($this->LoggedIn){ ?>
                <li><a id='menu_history'><?=$this->T('历史')?></a>
                    <?php if(!isset($this->WayBack) && $this->PageType!='search'){ ?>
                        | <a id='menu_edit'><?=$this->T('修改')?></a><?php } ?></li>
                <?php if(!isset($this->WayBack)){ ?><li>
                    <a id='menu_refer_copy'><?=$this->T('复制编号')?></a><?php if($this->PageType!='search'){ ?> 
                    | <a id='menu_refer'><?=$this->T('引用')?></a><?php } ?><br class='hidden_on_desktop' />
                </li>
                <li><a id='menu_rename' href='javascript:ToggleRenameDetails()'><?=$this->T('改名')?></a> |
                    <a id='menu_mark' href='javascript:ToggleMarkDetails()'><?=$this->T('标记')?></a></li>
                <li id='mark_details' style='display:none;'><b>
                    <a id='mark_set_clear' href='javascript:SetMark(-1);'>_</a>
                    <a id='mark_set_0' href='javascript:SetMark(0);'><?=$this->Markers[0]?></a>
                    <a id='mark_set_1' href='javascript:SetMark(1);'><?=$this->Markers[1]?></a>
                    <a id='mark_set_2' href='javascript:SetMark(2);'><?=$this->Markers[2]?></a>
                    <a id='mark_set_3' href='javascript:SetMark(3);'><?=$this->Markers[3]?></a>
                    <a id='mark_set_4' href='javascript:SetMark(4);'><?=$this->Markers[4]?></a>
                    <a id='mark_set_5' href='javascript:SetMark(5);'><?=$this->Markers[5]?></a>
                    <a id='mark_set_6' href='javascript:SetMark(6);'><?=$this->Markers[6]?></a>
                    <a id='mark_set_7' href='javascript:SetMark(7);'><?=$this->Markers[7]?></a>
                </b></li>
                <li id='rename_details' style='display:none;text-align:left;' class='smaller'>
                    <form action="" method="post" style='display:none;' id='post_rename_form'></form>
                    <input type='text' id='post_rename_name' name='post_rename_name' form="post_rename_form" style='width:9em;'>
                    <input class='button' type='submit' form='post_rename_form'
                        name='post_rename_confirm' id='post_rename_confirm' value='<?=$this->T('确认')?>'>
                </li><hr /> <?php if(isset($this->MastodonToken)){ ?>
                <li><a id='menu_mastodon_view' class='button' style='display:none;' target='_blank'><?=$this->T('转到')?></a>
                    <a id='menu_mastodon' href='javascript:ToggleMastodonDetails()'><?=$this->T('长毛象')?></a></li>
                <li id='mastodon_details' style='display:none;text-align:left;' class='smaller'>
                    <a id='menu_mastodon_send' class='button' target='_blank'><?=$this->T('发送')?></a>
                    <form action="" method="post" style='display:none;' id='post_mastodon_form'></form>
                    <input type='text' id='post_mastodon_url' name='post_mastodon_url' form="post_mastodon_form" style='width:9em;'>
                    <input class='button' type='submit' form='post_mastodon_form'
                        name='post_mastodon_confirm' id='post_mastodon_confirm' value='<?=$this->T('修改')?>'></li> <?php } ?>
                <hr /><li><a id='menu_delete' class='smaller'><?=$this->T('删除')?></a></li><?php } ?>
            <?php }else{ ?>
                <li><a id='menu_history'><?=$this->T('历史')?></a></li>
            <?php } ?>
            </ul>
        </div>
        <div id='backdrop' style='display:none;' class='backdrop' onclick='HideRightSide();HideBigImage(1);'
            ondrop="_dropHandler(event);" ondragover="_dragOverHandler(event);"></div>
        <div id='pop_right' class='pop_right' onclick='event.stopPropagation()'>
            <div style='text-align:right;position:sticky;top:0;'><a class='clean_a' href='javascript:HideRightSide();'>
                <?=$this->T('关闭')?>→</a></div>
            <?php if($this->LoggedIn && in_array($this->PageType,['main','post'])){ ?>
                <div id='_uploader' style='display:none;'>
                    <?php $this->MakeUploader(true); ?>
                    <p>&nbsp;</p>
                    <?php $this->MakeSideGalleryCode(); ?>
                </div>
            <?php } ?>
            <div class='_content'></div>
        </div>
    <?php } ?>
    <?php
    }
    
    function ChoosePartsByLanguage($content,$use_p=false){
        $split = preg_split('/'.($use_p?'<p>\s*':'').'((en|zh|any)\|(en|zh|any))'.($use_p?'<\/p>\s*':'').'/u',$content, -1,
            PREG_SPLIT_NO_EMPTY|PREG_SPLIT_DELIM_CAPTURE);
        $lang = $this->LanguageAppendix; $last_lang='0'; $reduced="";
        if($split) for($i=0;$i<sizeof($split);$i+=4){
            $this_lang = '0'; if($i+2>=sizeof($split)){ $this_lang = $last_lang; }else{ $this_lang = $split[$i+2]; }
            if($this_lang==$lang || $this_lang=='any'){ $reduced.=$split[$i]; }
            if($i+3<sizeof($split)){ $last_lang = $split[$i+3]; }
        }
        if(!$reduced)$reduced = $content;
        return trim($reduced);
    }
    function TranslatePostParts($html){
        $html = preg_replace_callback('/>([^><]+?)</u', function($ma){
                $reduced = $this->ChoosePartsByLanguage($ma[1], false);
                return ">".$this->T($reduced)."<";
            }, $html);
        return $html;
    }
    
    function MakeNavButtons(&$p){ $interesting=$this->IsInterestingPost($p); ?>
        <a href='index.php' class='clean_a hidden_on_mobile' onclick='ShowWaitingBar()'><b><?=$this->T($this->Title)?></b></a>
        <b><a class='hidden_on_desktop'
            href='javascript:toggle_mobile_show(document.getElementById("mobile_nav"))'><?=$this->T($this->ShortTitle)?>...</a></b> 
        <div class='header_nav'>
        <?php if($this->PageType=='post'){ ?>
            <div class='toc_button hidden_on_print'>
                <?php if(!$interesting && isset($p) && isset($p['refs']) && isset($p['refs'][0])){ ?>
                    <span class='hidden_on_desktop'><a id='button_ref' href='javascript:ToggleLeftSide();'>
                        <?=$this->T('链接')?>(<?=sizeof($p['refs'])?>)</a></span>
                <?php }  if(!$interesting){ ?>
                <span class='hidden_on_wide hidden_on_print'>
                    <a id='button_toc' href='javascript:ShowRightSide(true,document.querySelector("#div_right"));'>
                    <?=$this->T('目录')?></a></span><?php } ?></div>
        <?php }else if($this->PageType=='history' && isset($_GET['version'])){ ?>
            <div style='float:right;' class='hidden_on_print'>
                    <span class='hidden_on_desktop'><a id='button_ref' href='javascript:ToggleLeftSide();'>
                        <?=$this->T('历史')?></a></span></div>
        <?php } ?>
        <ul class='hidden_on_mobile hidden_on_print' id='mobile_nav' style='text-align:center;'>
            <li class='hidden_on_mobile'><a href='?gallery=default' onclick='ShowWaitingBar()'><?=$this->T('画廊')?></a></li>
            <li class='hidden_on_desktop_force block_on_mobile smaller'>&nbsp;</li>
            <li class='hidden_on_desktop_force block_on_mobile'>
            <?php if($this->PageType!='main'){ ?>
                <a href='index.php?part=recent' onclick='ShowWaitingBar()'><?=$this->T('最近')?></a> |
                <a href='index.php?part=hot' onclick='ShowWaitingBar()'><?=$this->T('热门')?></a>
            <?php } else { ?>
                <a href='javascript:ShowCenterSide();toggle_mobile_show(document.getElementById("mobile_nav"));'><?=$this->T('最近')?></a> |
                <a href='javascript:ShowLeftSide();toggle_mobile_show(document.getElementById("mobile_nav"));'><?=$this->T('热门')?></a>
            <?php } ?>
                <?php if($this->LoggedIn){ ?>
                    | <span class='gray invert_a'><a href='index.php?comments=all'>@</a></span><?php } ?></li>
            <li class='hidden_on_desktop_force block_on_mobile'>
                <a href='index.php?gallery=default' onclick='ShowWaitingBar()'><?=$this->T('画廊')?></a></li>
            <?php $this->SpecialNavigation;if(isset($this->SpecialNavigation) && ($p = &$this->GetPost($this->SpecialNavigation))!=NULL){
                echo $this->GenerateSinglePost($p, false, false, false, false,$this->NULL_POST);
            } ?>
            <li><a href='?search=' onclick='ShowWaitingBar()'><?=$this->T('搜索')?></a></li>
            <li class='hidden_on_desktop_force block_on_mobile smaller'>&nbsp;</li>
            <?php if($this->LanguageAppendix=='zh'){ ?>
                <li class='invert_a smaller'>
                    <a href='<?=$this->GetRedirect().'&set_language=en'?>' onclick='ShowWaitingBar()'><b>汉语</b>/English</a></li>
            <?php }else { ?>
                <li class='smaller'>
                    <a class='invert_a' href='<?=$this->GetRedirect().'&set_language=zh'?>' onclick='ShowWaitingBar()'>
                        汉语/<b>English</b></a>
                    <br class='hidden_on_desktop' />
                    <a id='translate_button' target='_blank'>&nbsp;[Google Translate]&nbsp;</a></li>
            <?php } ?>
        </ul>
        </div>
    <?php
    }
    function MakeExpNavButtons(&$p){ ?>
        <a href='index.php' class='hidden_on_mobile'>
            <b><?=$this->InExperimentalMode?$this->T($this->ExpTitle):$this->T($this->HereTitle)?></b></a>
        <a class='hidden_on_desktop'
            href='javascript:toggle_mobile_show(document.getElementById("mobile_nav"))'><b class='round_btn'>
                <?=$this->InExperimentalMode?$this->T($this->ExpShortTitle):$this->T($this->HereShortTitle)?></b></a>
        <a <?=$this->HereNumber==1?"class='gray'":("href=".$this->HereLinkFromNumber($this->HereNumber-1))?>>←</a>
        <span><?=$this->HereNumber.'/'.sizeof($this->VisitedHere);?></span>
        <span class='round_btn'>
            <a href='<?=$this->HereNumber==sizeof($this->VisitedHere)?"?random_here=1":$this->HereLinkFromNumber($this->HereNumber+1);?>'>→</a>
            <a href='?random_here=1'><?=$this->T("随机")?></a></span><span class='hidden_on_mobile'></span>
        <ul class='hidden_on_mobile invert_a gray' id='mobile_nav' style='text-align:center;'>
        <li class='smaller hidden_on_desktop'>&nbsp;</li>
        <li class='hidden_on_desktop block_on_mobile'><a href='index.php'><?=$this->T('主页')?></a></li>
        <?php if($this->InExperimentalMode && isset($this->ExpNavigation) && ($p = &$this->GetPost($this->ExpNavigation))!=NULL){
            echo $this->GenerateSinglePost($p, false, false, false, false,$this->NULL_POST);
        }else if($this->InHereMode && isset($this->HereNavigation) && ($p = &$this->GetPost($this->HereNavigation))!=NULL){
            echo $this->GenerateSinglePost($p, false, false, false, false,$this->NULL_POST);
        } ?>
        <li class='smaller hidden_on_desktop'>&nbsp;</li>
        <?php if($this->LanguageAppendix=='zh'){ ?>
            <li class='smaller'><a href='<?=$this->GetRedirect().'&set_language=en'?>'><b>汉语</b>/English</a></li>
        <?php }else { ?>
            <li class='smaller'><a href='<?=$this->GetRedirect().'&set_language=zh'?>'>汉语/<b>English</b></a></li>
            <li class='smaller'><a id='translate_button' target='_blank'>&nbsp;Google Translate&nbsp;</a></li>
        <?php } ?>
        </ul>
    <?php
    }
    
    function GenerateLinkedPosts($ht){
        $ht = preg_replace_callback('/<p>[\s]*<a[^>]*href=[\'\"]\?post=([0-9]{14})[\'\"][^>]*>(.*)<\/a>[\s]*<\/p>/u',
            function($m){
                $rp = &$this->GetPost($m[1], false, true);
                $s="<div class='smaller block post ref_compact gray'>".
                    "<a href='?post=".$m[1]."' class='post_access invert_a smaller' onclick='ShowWaitingBar()'>→</a>".
                    "<div class='post_ref'><div class='smaller'>".$m[2]."</div>".
                    (($rp!==NULL && $this->CanShowPost($rp))?
                        $this->GenerateSinglePost($rp,false,false,false,true,$this->NULL_POST,true):$this->T("未找到该引用。")).
                    "</div></div>";
                return $s;
            },
            $ht
        );
        $ht = preg_replace_callback('/<li>[\s]*<a[^>]*href=[\'\"]\?post=([0-9]{14})[\'\"][^>]*>PRODUCT\s+(.*)<\/a>[\s]*<\/li>/u',
            function($m){
                $rp = &$this->GetPost($m[1], false, true);
                $s="<div class='product_ref block post ref_compact'><a href='?post=".$m[1]."' class='clean_a' onclick='ShowWaitingBar()'>".
                    (($rp!==NULL && $this->CanShowPost($rp))?
                    $this->GenerateSinglePost($rp,true,false,false,true,$this->NULL_POST,true):$this->T("未找到该引用。")).
                    "</a></div>";
                return $s;
            },
            $ht
        );
        return $ht;
    }
    
    function ExtractBigTables($html, &$table_out){
        $table = NULL;
        $new_html = preg_replace_callback('/<p>\s*\{big_table\}\s*<\/p>\s*(<table>[\s\S]*?<\/table>)/u', function ($m) use (&$table){
            $table = $m[1];
            return "";
        }, $html);
        $table_out = $table;
        return $new_html;
    }

    function GenerateSinglePost(&$post, $strip_tags, $generate_anchor=false, $generate_refs=false,
                                 $generate_thumbs=false, &$table_out, $hide_long = false){
        $this->ConvertPost($post);
        if($generate_anchor){ $this->CreatePostAnchor($post); }
        $ht = $post['html'];
        if(isset($post['mark_value']) && $post['mark_value']==5 && ($generate_thumbs || !$generate_anchor)){
            $ht="<div class='product_thumb'>".$post['images'][0]."</div>".
                "<p class='bold'>".$post['product']['title']."</p>".
                (isset($post['product']['short'])?("<p class='smaller gray'>".$post['product']['short']."</p>"):"").
                "<p class='smaller bold'>".$post['product']['price']."</p>";
            return $ht;
        }
        if($hide_long){
            $ht = preg_replace('/<p>\s*\{read_more\}\s*<\/p>[\s\S]*/u',"<p class='smaller bold'>[".$this->T("阅读更多")."]</p>", $ht);
        }else{
            $ht = preg_replace('/<p>\s*\{read_more\}\s*<\/p>/u',"", $ht);
        }
        if ($strip_tags){
            $ht = strip_tags($ht,'<b><i><h1><h2><h3><h4><p><blockquote><span>');
            $ht = preg_replace('/<p>\s*<\/p>/u',"", $ht);
            $ht = "<div class='post_ref_main'>".$ht."</div>";
        }
        else{
            if($generate_refs) $ht = $this->GenerateLinkedPosts($ht);
            if($table_out!=$this->NULL_POST){
                $table = NULL; $ht = $this->ExtractBigTables($ht,$table);
                $table_out = $table;
            }else{
                $ht = preg_replace('/<p>\s*\{big_table\}\s*<\/p>/u','',$ht);
            }
        }
        if ($strip_tags && $generate_thumbs && isset($post['images']) && isset($post['images'][0])){
            $ht.="<div class='post_ref_images'><ul class='side_thumb ref_thumb'>";
            foreach($post['images'] as $im){
                $ht.="<li class='file_thumb'>".$im."</li>";
            }
            $ht.="</ul></div>";
        }
        return $ht;
    }
    
    function MakeSinglePostExp(&$post){ 
        $big_table = ""; ?>
        <li class='post post_dummy'>
            <?=$this->GenerateSinglePost($post, false, false, true, false, $big_table, false); ?>
        </li>
        <?php if($big_table!=$this->NULL_POST) echo "</ul></li><div class='table_top'>".$big_table.'</div>';?>
    <?php
    }
    function MakeSinglePost(&$post, $show_link=false, $side=false, $extra_class_string=NULL,
                                    $strip_tags=false, $show_thread_link=false, $show_reply_count=false, $generate_anchor=false,
                                    $generate_thumb = false, $is_top = false, $force_hide_long=false){
        $is_deleted = (isset($post['mark_delete'])&&$post['mark_delete']);
        $mark_value = isset($post['mark_value'])?$this->Markers[$post['mark_value']]:-1;
        $ref_count = isset($post['refs'])?sizeof($post['refs']):0;
        $big_table = ($show_thread_link)?$this->NULL_POST:"";
        $is_product = isset($post['mark_value'])&&$post['mark_value']==5;
        $title = $generate_anchor?$this->GetPostTitle($post, true):NULL;
        $this->ConvertPost($post);// NEEDED FOR WIDE;
        $is_wide = (isset($post['wide'])&&$post['wide']) || (isset($post['tid']) && isset($post['tid']['wide']) && $post['tid']['wide']);
        ?>
        <?=$title?"<li class='print_title'><h1>".$title."</h1></li>":""?>
        <li class='post<?=isset($extra_class_string)?' '.$extra_class_string:''?><?=$side?" post_box":""?>'
            <?=(!$side)?"data-post-id='".$post['id']."'":""?> <?=$is_deleted?"data-mark-delete='true'":""?>
            <?=(!$side&&isset($post['mastodon_url']))?"data-mastodon_url='".$post['mastodon_url']."'":""?>
            <?=$is_wide?" style='column-span:all;'":"";?>>
            <?php if($mark_value>=0 && !$show_link && $mark_value!='P'){?>
                <div class='smaller <?=$is_deleted?"gray":""?>'><?=$mark_value?> <?=$this->T('标记')?></div>
            <?php } ?>
            <?php if($is_top){?>
                <div class='top_post_hint'><?=$this->T('置顶帖子')?><hr /></div>
            <?php } ?>
            <?php if(!$side && $show_link){ ?>
                <a href='?post=<?=$post['id']?>' onclick='ShowWaitingBar()'>
                <div class='post_access <?=($mark_value<0 || $ref_count)?"invert_a":""?> hover_dark'>
                    <?=isset($post['mark_value'])?$this->Markers[$post['mark_value']]:($ref_count?"":"→")?>
                </div>
                <?php if($ref_count){ ?>
                    <div class='post_access ref_count'><?=$ref_count?></div>
                <?php } ?>
                </a>
            <?php } ?>
            <?=$side?"<a href='?post={$post['id']}' onclick='ShowWaitingBar()'>":""?>
            <div class='<?=$side?"":($show_link?'post_width':'post_width_big')?><?=$is_deleted?" deleted_post":""?>'>
                    <?php if(!$side && !$strip_tags){?>
                        <div class='post_menu_button _menu_hook' >+</div>
                    <?php } if($is_product&&!$generate_anchor){
                        echo "<p class='smaller gray'>".$this->T('商品')."</p>";
                        echo "<div class='product_ref clean_a'><a href='?post={$post['id']}'>";} ?>
                    <?=$this->GenerateSinglePost($post, $strip_tags, $generate_anchor, true,
                                                     $generate_thumb,$big_table,($show_thread_link||$side||$force_hide_long)); ?>
                    <?php if($is_product&&!$generate_anchor){echo "</a></div>";} ?>
            </div>
            <?=$side?"</a>":""?> <?php
            if(!$show_thread_link && $big_table!=$this->NULL_POST && !$side){
                echo "</ul></li><div class='table_top'>".$big_table.'</div>';
            ?><ul<?=$is_wide?"":" class='print_column'"?>>
                <li class='post<?=isset($extra_class_string)?' '.$extra_class_string:''?>' <?=$is_deleted?"data-mark-delete='true'":""?>>
            <?php
            }
            if($show_thread_link && isset($post['tid']) && $this->CanShowPost($post['tid']['first']) && 
                $post['tid']['first']['id']!=$post['id']){ ?>
                <div class='gray smaller block opt_compact post'>
                    <a href='?post=<?=$post['tid']['first']['id']?>' onclick='ShowWaitingBar()'>
                        <div class='post_access invert_a hover_dark smaller'><?=isset($post['tid']['first']['mark_value'])?
                                $this->Markers[$post['tid']['first']['mark_value']]:"→"?></div></a>
                    <div class='post_width'><div class='smaller'><?=$this->T('回复给主题帖：')?></div>
                        <?=$this->GenerateSinglePost($post['tid']['first'], false, false, false, true,$this->NULL_POST,true);?>
                    </div>
                </div>
            <?php }
            if($show_reply_count && isset($post['tid'])){ ?>
                <a class='smaller block invert_a' href='?post=<?=$post['tid']['last']['id']?>'><?=$post['tid']['count']?>
                    <?=$this->T('个回复')?></a>
            <?php }
            ?>    
        </li>
    <?php
    }
    
    function MakePostingFields($reply_to=NULL, $show_hint=false){
        if(isset($this->WayBack)){ ?>
            <span class='gray'><i><?=$this->T('以过去日期浏览时不能发帖。')?></i></span>
        <?php return; } ?>
        <form action="<?=$_SERVER['REQUEST_URI']?>" method="post" style='display:none;' id='post_form'></form>
        <div class='smaller'><div style='display:<?=$show_hint?"block":"none"?>;'>
            <span id='post_hint_text'><?=$this->T('继续补充该话题：')?></span></div>
            <div id='post_hint_modify' style='display:none;'>
                <input type="checkbox" name="post_record_edit" value="1" form='post_form' checked> <?=$this->T('新增历史记录');?></div></div>
        <textarea id="post_content" name="post_content" rows="4" form='post_form'
                  onfocus="if (value =='<?=$this->T('有什么想说的')?>'){value ='';}la_auto_grow(this);"
                  onblur="if (value ==''){value='<?=$this->T('有什么想说的')?>';la_auto_grow(this);}"    
                  oninput="la_auto_grow(this);" onload="la_auto_grow(this);"><?=$this->T('有什么想说的')?></textarea>
        <input class='button' form="post_form" type="submit" name='post_button' value=<?=$this->T('发送')?> 
            onclick='ShowWaitingBar();' />
        | <a class='gray smaller pointer' onclick='ShowSideUploader();'><?=$this->T('图片')?></a>
        <div style='float:right;'>
            <a class='gray smaller pointer' onclick='t=document.querySelector("#post_content");t.value="";la_auto_grow(t);'>
                <?=$this->T('清空')?></a>
            <a class='gray smaller pointer' style='display:none;' id='post_reply_restore' href='javascript:RestoreReply()'></a>
        </div>
        <input style='display:none;' type=input form="post_form" id='post_edit_target' name='post_edit_target' />
        <script> la_auto_grow(document.getElementById("post_content"));</script>
        <?php if($reply_to){ ?>
            <input style='display:none;' type=input form="post_form" id='post_reply_to' name='post_reply_to' value='<?=$reply_to?>' />
        <?php } ?>
    <?php
    }
    
    function MakeCommentPosts(){
        if(!$this->LoggedIn) return; ?>
        <h2><?=$this->T('评论')?></h2><div class='spacer'></div>
        <?php if(!$this->CommentEnabled){
            echo "<p><span class='text_highlight'>&nbsp;".$this->T("已关闭评论")."&nbsp;</span></p><br />"; } ?>
        <div class='comment'><ul>
        <?php $i=0;
            foreach(array_reverse($this->Posts) as $p){
                if(!isset($p['comment_to'])) continue;
                if($i < $this->CommentsPerPage * $this->CurrentOffset) {$i++; continue;}
                
                $ht = $this->GenerateSinglePost($p, false, false, false, false, $t, false);
                $name = isset($p['link'])?("<a href='".$p['link']."'>".$p['name']."↗</a>"):$p['name'];
                $post_to = $this->GetPost($p['comment_to']); $post_title = $this->GetPostTitle($post_to);
                if(!$post_to) $post_title = "?";
                $mail = "<span class='gray hidden_on_print'>&nbsp;".
                        "<a href='mailto:".$p['email']."'>@</a>&nbsp;".
                        (isset($p['ip'])?$p['ip']:"?")."&nbsp;<br class='hidden_on_desktop'/>".
                        "<a href='index.php?post=".$p['comment_to']."'>(".$post_title.")</a></span>";
                echo "<li><p><b>".$name.":</b>".$mail."<br /></p>".$ht."</li>";
                
                $i++;
            }
        ?>
        </ul><br /></div>
    <?php
    }
    
    function MakeRecentPosts($search_term=NULL, $category=NULL){?>
        <div class='center' id='div_center'>
            <h2><?=isset($search_term)?$this->T('搜索'):
                                (isset($category)?("<span class='gray'>".$this->T('分类')."</span> ".
                                    ($category=='none'?$this->T('未分类'):$this->T($category))):($this->T('最近')).
                                    " <span class='gray invert_a hidden_on_print'>".//"<a href='index.php?&set_wayback=true'>↶</a>".
                                    ($this->LoggedIn?"<a href='index.php?comments=all'>@</a>":"")."</span>")?></h2>
            <?php if(isset($search_term)){ ?>
                <form action="index.php" method="post" style='display:none;' id='search_form'></form>
                <input id="search_content" name="search_content" rows="4" form='search_form' type='text' value='<?=$search_term?>'>
                <input class='button' form="search_form" type="submit" name='search_button' value=<?=$this->T('搜索')?>
            <?php }else if(isset($category)){ ?>
                <div></div>
            <?php }else if($this->LoggedIn){ ?>
                <div class='post_box_top _input_bundle'>
                    <?php $this->MakePostingFields(NULL,false); ?>
                </div>
            <?php } ?>
            <ul class='print_column list'>
                <?php
                    if(!isset($search_term) && !isset($category) &&
                       (isset($this->SpecialPinned) && ($p = &$this->GetPost($this->SpecialPinned))!=NULL && !$this->CurrentOffset) &&
                       $this->CanShowPost($p)){
                        $this->MakeSinglePost($p, true, false, false, false, true, false, false, false, true, false);
                    }
                    $i = 0; $last_end_wide = true;
                    if(isset($this->UsePosts[0])) foreach(array_reverse($this->UsePosts) as &$p){
                        if(!$this->CanShowPost($p) || $this->SkipProduct($p)) continue;
                        if(isset($p['tid'])){ /* Should always be set. */
                            $th = &$p['tid']; if($p['tid']['count']==0) continue;
                            if(!isset($search_term)) { $p = &$th['last']; }
                        }
                        if(isset($search_term)){
                            if ($search_term=='' || !preg_match("/".preg_quote($search_term)."/iu", $p['content'])) continue;
                        }else if(isset($category)){
                            $cat = isset($p['tid'])?(isset($p['tid']['categories'])?($p['tid']['categories']):[]):
                                                    (isset($p['categories'])?($p['categories']):[]);
                            if ($category=='none') { if($cat!=[]) continue; }
                            else{ if ($category=='' || !in_array($category, $cat)) continue; }
                            if(isset($p['tid'])){ if(isset($p['tid']['displayed'])) continue; $p['tid']['displayed'] = True; }
                        }else{
                            if(in_array($p['id'],
                                [$this->SpecialPinned,$this->SpecialFooter,$this->SpecialFooter2,$this->SpecialNavigation])) continue;
                            if(isset($p['tid'])){ if(isset($p['tid']['displayed'])) continue; $p['tid']['displayed'] = True; }
                        }
                        if($i < $this->PostsPerPage * $this->CurrentOffset) {$i++; continue;}
                        if($last_end_wide){$use_class="last_wide";}else{$use_class="";}
                        $this->MakeSinglePost($p, true, false, $use_class, false, !isset($search_term), false, false, false, false, isset($search_term));
                        $i++;
                        if($i >= $this->PostsPerPage * (1+$this->CurrentOffset)) {break;}
                        $last_end_wide = sizeof($p['tid']['arr'])==1 && isset($p['end_wide']) && $p['end_wide'];
                    }?>
            </ul>
            <div class='page_selector clean_a'>
                <hr />
                <a <?=$this->CurrentOffset>0?("href='index.php?offset=".($this->CurrentOffset-1).
                            (isset($search_term)?"&search=".$search_term:(isset($category)?"&category=".$category:""))."'"):""?>
                   <?=$this->CurrentOffset==0?"class='gray'":""?>><?=$this->T('上一页')?> ←</a>
                <?=$this->CurrentOffset+1?>
                <a href='index.php?offset=<?=($this->CurrentOffset+1).
                    (isset($search_term)?"&search=".$search_term:(isset($category)?"&category=".$category:""))?>'>
                    → <?=$this->T('下一页')?></a>
            </div>
        </div>
    <?php
    }
    
    function MakeHotPosts($placeholder_only=false){?>
        <div class='left hidden_on_mobile' id='div_left'>
            <?php if ($placeholder_only){ ?>&nbsp;
            <?php }else{ ?>
            <h2><?=$this->T('热门')?></h2>
            <ul>
                <?php
                    $i=0;
                    foreach($this->Threads as &$th){
                        if(!isset($th['first']) || $th['count']==0){ continue; }
                        if(!$this->CanShowPost($th['first'])) continue;
                        if($i>=$this->HotPostCount) break;
                        $this->MakeSinglePost($th['first'], false, true, "post_preview", true, false, true, false, true, false, false);
                        $i++;
                    } ?>
            </ul>
            <?php } ?>
        </div>
    <?php
    }
    
    function MakeLinkedPosts(&$p){
        $has_ref = isset($p['refs'])&&isset($p['refs'][0]); ?>
        <div class='left hidden_on_mobile' id='div_left'>
        <h2<?=$has_ref?"":" class='gray'"?>><?=$this->T('链接')?></h2>
        <?php
            if($has_ref){ ?>
                <span class='smaller'><?=sizeof($p['refs'])?> <?=$this->T('个引用：')?></span>
                <ul><?php $count_product=0;
                foreach(array_reverse($p['refs']) as &$pr){
                    $po = $this->GetPost($pr); if(isset($post['mark_value']) && $po['mark_value']==5){ $count_product++; continue; }
                    if(!$this->CanShowPost($po)){ continue; }
                    $this->MakeSinglePost($po, false, true, "post_preview", true, false, false, false, true, false, false);
                } 
                ?></ul>
            <?php if($count_product){ ?> <span class='smaller'><?=$this->T('和').' '.$count_product.' '.$this->T('个商品')?></span> <?php }
                }else{ ?>
                <span class='gray smaller'><?=$this->T('没有帖子链接到这里。')?></span>
            <?php } ?>
        </div>
    <?php
    }
    function MakeLinkedPostsExp(&$p){
        $has_ref = isset($p['refs'])&&isset($p['refs'][0]); ?>
        <div class='center_exp post_dummy smaller'>
        <hr />
        <h3<?=$has_ref?"":" class='gray'"?>><?=$this->T('链接')?></h3>
        <?php
            if($has_ref){ ?>
                <div class='smaller'><?=sizeof($p['refs'])?> <?=$this->T('个引用：')?></div>
                <ul><?php $count_product=0;
                foreach(array_reverse($p['refs']) as &$pr){
                    echo "<li class='post_dummy'><a href='?post=".$pr."'>".$this->T($this->GetPostTitle($this->GetPost($pr),false)).
                         "</a></li>";
                } 
                ?></ul>
            <?php if($count_product){ ?> <span class='smaller'><?=$this->T('和').' '.$count_product.' '.$this->T('个商品')?></span> <?php }
                }else{ ?>
                <div class='gray smaller'><?=$this->T('没有帖子链接到这里。')?></div>
            <?php } ?>
        <div style='margin-bottom:4rem;'>&nbsp;</div>
        </div>
    <?php
    }
    
    function MakePostHistoryList(&$ah, &$post,$version=NULL, $show_latest=true){
        $latest_ver=isset($post['version'])?$post['version']:$post['id'];
        $can_show=1; if(isset($this->WayBack) && $latest_ver>$this->WayBack) $can_show=0;
        $title = NULL; if(!isset($ah) || ($show_latest && $can_show)) { $title=$this->T($this->GetPostTitle($post, false)); }
        else{ $ver = &$ah['list'][sizeof($ah['list'])-1]; $title= $this->GetPostTitle($ver); }
        if(!isset($title)) $title=$this->T('未命名');
        if(!isset($ah)){ ?>
            <h2><?=$this->T('没有历史记录')?></h2>
            <span class='omittable_title smaller'><?=$this->T('帖子')?> <a href='?post=<?=$post['id']?>'><?=$title?></a></span><br />
        <?php }else{ ?>
            <h2><?=$this->T('历史记录')?></h2>
            <span class='omittable_title smaller'><?=$this->T('帖子')?> <a href='?post=<?=$ah['id']?>'><?=$title?></a></span><br />
            <div class='list'><ul>
            <?php if($show_latest && $can_show){  $post['history_displayed']=1; ?>
            <li class='<?=isset($version)&&$version==$latest_ver?" post_current_row bold":""?>' style='list-style:"";'>
                <a href='?post=<?=$this->CurrentPostID?>&history=1&version=<?=$latest_ver?>'><?=$this->ReadableTime($latest_ver)?></a>
                <span class='smaller gray'><?=$this->T('长度').' '.strlen($post['content'])?></span>
                <?php if(isset($post['merged_from'][0])){ ?><ul><?php foreach($post['merged_from'] as $from){ 
                    $fromah = &$this->GetArchiveHandle($from); if(!isset($fromah)) continue;
                    $ver = &$fromah['list'][sizeof($fromah['list'])-1];?>
                    <li><a href='?post=<?=$ver['id']?>&history=1'>
                            <?=$this->GetPostTitle($ver);?></a>
                        <span class='smaller gray'><?=$this->T('长度').' '.strlen($ver['content'])?></span></li>
                <?php } ?></ul><?php } ?>
                <?php if(isset($post['merged_thread'][0][0])){ $pm=$post['merged_thread'][0][0];
                    $mah = &$this->GetArchiveHandle($pm); 
                    $mver = &$this->GetPost($pm); if(!isset($mver) && isset($mah)) $mver = &$mah['list'][sizeof($mah['list'])-1]; ?>
                    <ul><li><?=$this->T('话题')?> <a href='?post=<?=$mver['id']?>&history=1'><?=$this->GetPostTitle($mver);?></a> <?=$this->T('并入这里')?>
                        <span class='smaller gray'><?=sizeof($post['merged_thread'][0]);?> <?=$this->T('个帖子')?></span></li></ul>
                <?php } ?>
            </li><?php } ?>
            <?php if(isset($ah['list'][0])) foreach(array_reverse($ah['list']) as &$ver){ 
                if(isset($this->WayBack) && $ver['version']>$this->WayBack) continue;
                if(isset($ver['history_displayed']) && $ver['history_displayed']) continue; ?>
                <li<?=isset($version)&&$version==$ver['version']?" class='post_current_row bold'":""?>>
                    <a href='?post=<?=$this->CurrentPostID?>&history=1&version=<?=$ver['version']?>'><?=$this->ReadableTime($ver['version'])?></a>
                    <span class='smaller gray'> <?=$this->T('长度').' '.strlen($ver['content'])?></span>
                    <?php if(isset($ver['merged_thread'][0][0])){ $pm=$ver['merged_thread'][0][0];
                        $mah = &$this->GetArchiveHandle($pm); $mver = &$this->GetPost($pm);
                        if(!isset($mver) && isset($mah)) $mver = &$mah['list'][sizeof($mah['list'])-1]; ?>
                        <ul><li><?=$this->T('话题')?> <a href='?post=<?=$mver['id']?>&history=1'><?=$this->GetPostTitle($mver);?></a> <?=$this->T('并入这里')?>
                            <span class='smaller gray'> <?=sizeof($ver['merged_thread'][0]);?> <?=$this->T('个帖子')?> </span></li></ul>
                    <?php } if(isset($ver['merged_from'][0])){ ?><ul><?php foreach($ver['merged_from'] as $from){ 
                        $fromah = &$this->GetArchiveHandle($from); if(!isset($fromah)) continue;
                        $ver = &$fromah['list'][sizeof($fromah['list'])-1];?>
                        <li><a href='?post=<?=$ver['id']?>&history=1'>
                                <?=$this->GetPostTitle($ver);?></a>
                            <span class='smaller gray'><?=$this->T('长度').' '.strlen($ver['content'])?></span></li>
                    <?php } ?></ul></li><?php } ?>
            <?php } ?></ul></div>
        <?php } ?>
    <?php
    }
    
    function MakePostDiff(&$this_ver, &$last_ver){
        if(!isset($this_ver)){ ?>
            <h2 class='gray'><?=$this->T('版本不存在')?></h2>
        <?php }else{ ?>    
            <h2><?=$this->T('差异')?></h2>
            <?php if(isset($this_ver['merged_thread']) && isset($this_ver['merged_thread'][0][0])){
                $pm=$this_ver['merged_thread'][0][0]; $mah = &$this->GetArchiveHandle($pm); 
                $mver = &$this->GetPost($pm); if(!isset($mver) && isset($mah)) $mver = &$mah['list'][sizeof($mah['list'])-1]; ?>
                <p><?=$this->T('话题')?> <a href='?post=<?=$mver['id']?>&history=1'><?=$this->GetPostTitle($mver)?></a> <?=$this->T('并入这里')?>
                    <br /><span class='smaller gray'> <?=sizeof($this_ver['merged_thread'][0]);?> <?=$this->T('个帖子')?> </span></p>
            <?php }else{ ?>
                <table class='diff_table'><thead>
                    <tr><td><?php if(!isset($last_ver)){ ?><?=$this->T('没有更旧的版本')?><?php }else{ ?>
                        <?=$this->T('上一个版本')?><br /><?=$this->ReadableTime($last_ver['version'])?><?php } ?></td>
                        <td class='text_highlight bold'><?=$this->T('选择的版本')?><br /><?=$this->ReadableTime($this_ver['version'])?></td></tr>
                </thead><tbody>
                    <tr><td><pre><?=isset($last_ver)?$last_ver['content']:""?></pre></td><td><pre><?=$this_ver['content']?></pre></td></tr>
                </tbody>
                </table>
            <?php } ?>
            <br /><a class='smaller' href='?post=<?=$this_ver['id']?>&set_wayback=<?=isset($this_ver['version'])?$this_ver['version']:$this_ver['id']?>'><?=$this->T('前往该版本时间')?> ←</a>
        <?php } ?>
    <?php
    }
    function MakePostHistory(&$post, $version=NULL){
        $ah = &$this->GetArchiveHandle($this->CurrentPostID);
        $p = &$post; if(!isset($post)){ $p=&$ah['list'][0]; }
        $show_latest = isset($ah)?(!isset($ah['list'][sizeof($ah['list'])-1]['merged_into'])):true;
        ?>
        <div class='left hidden_on_mobile history' id='div_left'>
            <?php if (isset($version)){
                $this->MakePostHistoryList($ah, $p, $version, $show_latest);
            }else{ echo "&nbsp;"; } ?>
        </div>
        <div class='center history' id='div_center'>
            <?php if (!isset($version)){
                $this->MakePostHistoryList($ah, $p, NULL, $show_latest);
            }else{
                $this_ver = NULL; $next_ver = NULL; $last_ver=NULL;
                $this_ver = &$this->GetArchiveVersion($ah, $version, $next_ver, $last_ver);
                if(!isset($this_ver)){$this_ver = $p; $last_ver=$ah['list'][sizeof($ah['list'])-1];}
                $this->MakePostDiff($this_ver, $last_ver, $p);
            } ?>
        </div>
    <?php
    }
    
    function MakeCommentSection(&$post){
        if(!$this->CommentEnabled){ return; }
        $to_post = isset($post['tid'])?$post['tid']['first']:$post;
        $comment_count = (isset($to_post['comments']) && isset($to_post['comments'][0]))?count($to_post['comments']):0;
        ?><div class='comment'>
        <br /><h2><?=$this->T('评论')?> (<?=$comment_count;?>)</h2><div class='spacer'></div>
            <?php if($comment_count) { echo "<ul>";
                    foreach($to_post['comments'] as $p){
                        $ht = $this->GenerateSinglePost($p, false, false, false, false, $t, false);
                        $name = isset($p['link'])?("<a href='".$p['link']."'>".$p['name']."↗</a>"):$p['name'];
                        $mail = $this->LoggedIn?("<span class='gray clean_a hidden_on_print'>&nbsp;".
                                                 "<a href='mailto:".$p['email']."'>@</a>&nbsp;</span>"):"";
                        echo "<li><p><b>".$name.":</b>".$mail."</p>".$ht."</li>";
                    }
                    echo "</ul>";
            } else {
                echo $this->T('还没有评论');
            } ?>
            <div class='hidden_on_print'>
                <div class='spacer'></div>
                <form action="index.php?post=<?=$this->CurrentPostID?>&comment_to=<?=$to_post['id']?>"
                    method="post" style="display:none;" id="comment_form"></form>
                <p><a class='text_highlight bold clean_a'
                      onclick='document.getElementById("comment_box").style.display="block";this.parentNode.style.display="none"'>
                      &nbsp;<?=$this->T('写评论');?>&nbsp;</a></p>
                <div id='comment_box' style='display:none;'>
                    <p class='gray' style='margin-bottom:0.5em;'><?=$this->T('您的邮箱不会公开展示。');?></p>
                    <table style='white-space:nowrap;'>
                        <tr><td colspan='2'>
                            <textarea id="comment_box" name="comment_box" rows="4" class='full_box' form='comment_form'
                                oninput="CommentUpdated();" ></textarea>
                        </td></tr>
                        <tr><td><?=$this->T('电子邮件')?>*</td><td><?=$this->T('称呼')?>*</td></tr>
                        <tr><td><input type="text" form="comment_form" id='comment_email' name='comment_email'
                                    class='full_box' oninput="CommentUpdated();" /></td>
                            <td><input type="text" form="comment_form" id='comment_name' name='comment_name'
                                    class='full_box' oninput="CommentUpdated();" /></td></tr>
                        <tr><td colspan='2'><?=$this->T('个人网站')?></td></tr>
                        <tr><td colspan='2'>
                            <input type="text" form="comment_form" id='comment_link' name='comment_link'
                                    class='full_box' oninput="CommentUpdated();" />
                        </td></tr>
                        <tr><td colspan='2'>
                            <div class='spacer'></div>
                            <input class='button text_highlight bigger' type='submit' form='comment_form'
                                name='comment_confirm' id='comment_confirm' value='&nbsp;<?=$this->T('发送')?>&nbsp;'>
                        </td></tr>
                    </table></div>
                <script>
                const IsValidEmail = (email) => {
                  return String(email).toLowerCase().match(/^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/);
                };
                function IsValidHttpUrl(string) {
                  let url; try{ url = new URL(string); } catch (_) { return false; }
                  return url.protocol === "http:" || url.protocol === "https:";
                }
                var cbox = document.getElementById('comment_box');
                var cemail = document.getElementById('comment_email');
                var cname = document.getElementById('comment_name');
                var clink = document.getElementById('comment_link');
                var cconfirm = document.getElementById('comment_confirm');
                function CommentUpdated(){
                    cconfirm.disabled=true;
                    if (cemail.value!="" && IsValidEmail(cemail.value) &&
                        cbox.innerText.length>2 && cname.value.length>2 &&
                        clink.value=="" || IsValidHttpUrl(clink.value)){
                        cconfirm.removeAttribute("disabled");
                    }
                }
                CommentUpdated();
                </script>
            </div>
        </div>
    <?php
    }
    
    function MakeInterestingSection(&$th){
        $this->Anchors = [];  
        $c = &$th['first']['content'];
        $this->ConvertPost($th['first']);
        $ht = $th['first']['html'];
        ?>
        <script>function ClickImage(post_access){im=post_access.querySelector('img');if(im){im.click();}}</script>
        <div class='center_full' id='div_center'>
            <?php $this->MakePostTools(); ?>
            <?php $cat = NULL; 
            if(isset($th['categories']) && isset($th['categories'][0])){ $cat = $th['categories']; }
            if($cat){ ?>
                <p><b><?=$this->T('分类')?></b> | <?php foreach($cat as $c){ 
                    echo "<a href='?category=".$c."'>".($c=='none'?$this->T('未分类'):$this->T($c))."</a> "; } ?></p>
            <?php } ?>
            <h2 class='hidden_on_print'><?=$this->T('有趣')?>
                <a class='gray clean_a' href='?post=<?=$th['first']['id']?>'>→</a></h2>
            <ul><li class='post post_width_big' data-post-id='<?=$th['first']['id']?>'>
                <div class='post_menu_button _menu_hook' onclick='ShowPostMenu(this.parentNode);'>+</div><?=$ht?>
                <?php if(isset($th['first']['refs'][0])){ ?><p class='smaller'><?=$this->T('引用')?>: </p><ul class='smaller'>
                    <?php foreach($th['first']['refs'] as $ref){
                        $po = $this->GetPost($ref);
                        if(isset($post['mark_value']) && $post['mark_value']==5){ $count_product++; continue; }
                        if(!$this->CanShowPost($po)){ continue; }
                        echo "<li><a href='?post=".$po['id']."'>".$this->GetPostTitle($po)."</a></li>";
                    } ?>
                </ul><?php } ?>
                <?php if($this->LoggedIn && (!$this->InHereMode)){ ?>
                    <div class='post_width_big hidden_on_print'><br />
                        <?php $this->MakePostingFields($th['last']['id'], true);?><br />
                    </div>
                <?php } ?>
                <table><thead><tr><th></th><?php foreach($th['interesting'] as $header){ ?>
                    <th><?=$this->T($header);?></th>
                <?php } ?></tr></thead><tbody class='interesting_tbody'>
                <?php $plist = array_reverse(array_slice($th['arr'],1));
                foreach($plist as &$p){
                    $this->ConvertPost($p);
                    $mark_value = isset($p['mark_value'])?$this->Markers[$p['mark_value']]:-1;
                    $ref_count = isset($p['refs'])?sizeof($p['refs']):0;
                    $is_current = $p['id']==$this->CurrentPostID;
                    $is_deleted = (isset($p['mark_delete'])&&$p['mark_delete']);  ?>
                    <tr class='<?=$is_current?"post_current_row":""?><?=$is_deleted?" deleted_post":""?>'
                        data-post-id='<?=$p['id']?>' <?=$is_deleted?"data-mark-delete='true'":""?>
                        onDblClick='ClickImage(this)'><td>
                    <a <?=$is_current?"":("href='?post=".$p['id']."' onclick='ShowWaitingBar()'");?> class='clean_a paa'>
                        <div class='post_access <?=($mark_value<0 || $ref_count)?"invert_a":""?> hover_dark'>
                            <?=isset($p['mark_value'])?$this->Markers[$p['mark_value']]:($ref_count?"":"→")?>
                        </div>
                        <?php if($ref_count){ ?>
                            <div class='post_access ref_count'><?=$ref_count?></div>
                        <?php } ?></a>
                    <?=$p['html'];?>
                    <a class='_menu_hook clean_a post_menu_button' onclick='ShowPostMenu(this.parentNode.parentNode);'>+</a></td></tr>
                    <?php if($is_current && $ref_count>0){ ?><tr class='post_current_row'><td><p>&nbsp;</p><ul class='smaller'>
                        <?php foreach($p['refs'] as $ref){
                            $po = $this->GetPost($ref);
                            if(isset($post['mark_value']) && $post['mark_value']==5){ $count_product++; continue; }
                            if(!$this->CanShowPost($po)){ continue; }
                            echo "<li><a href='?post=".$po['id']."'>".$this->GetPostTitle($po)."</a></li>";
                        } ?>
                    </ul></td></tr><?php } ?>
                <?php } ?>
                </tbody></table>
            </li></ul>
            <br />
            <?php $this->MakeCommentSection($th['first']); ?>
        </div>
        <?php return true;
    }
    function CssNumberID($id){
        return "#\\3".substr($id,0,1)." ".substr($id,1);
    }
    function MakePostTools(){
        if($this->LoggedIn && !isset($this->WayBack)){ ?><div class='smaller gray hidden_on_print clean_a'>
            <a id='merge_cancel' href='javascript:{TogglePostSelectMode(false,true);ToggleThreadMerge(false,true);}'>
                <?=$this->T('工具')?></a>:<div class='hidden_on_mobile' style='display:inline'>
                <span id='merge_post_btn'><a href='javascript:TogglePostSelectMode()'><?=$this->T('合并帖子')?></a></span>
                | <span id='merge_thread_btn'><a href='javascript:ToggleThreadMerge()'><?=$this->T('合并话题')?></a></span>
            </div>
            <select class='hidden_on_desktop' id='merge_select'
                onchange="if(this.selectedIndex==1){TogglePostSelectMode(true);}else if(this.selectedIndex==2){ToggleThreadMerge(true);}">
                <option value='-1' selected>--</option>
                <option value='1'><?=$this->T('合并帖子')?></option>
                <option value='2'><?=$this->T('合并话题')?></option></select>
            <div style='display:none;' id='merge_post_dialog' class='text_highlight small_pad align_right'>
                <div style='display:inline;'><?=$this->T('将合并');?> <span id='merge_post_count'></span> <?=$this->T('个帖子');?></div>
            <span class='clean_a bold align_right'><a id='merge_post'>&nbsp;<?=$this->T('执行');?></a></span></div>
            <div style='display:none;overflow:auto' id='merge_thread_dialog' class='text_highlight small_pad align_right'>
                <div style='display:inline;'><?=$this->T('合并到话题');?></div>
                <input id="merge_thread_target" type="text" style="width:9em;display:inline" onChange='ThreadMergeInput(this);' onInput='ThreadMergeInput(this);'>
                <span class='clean_a bold align_right'><a id='merge_thread'>&nbsp;<?=$this->T('执行');?></a></span></div>
            </div><div class='spacer hidden_on_print'></div><?php
        }
    }
    function MakePostSection(&$post){
        $this->Anchors = [];
        if(isset($this->TagID)){ ?><style><?=$this->CssNumberID($this->TagID);?>{display:block;}</style><?php } ?>
        <div class='center' id='div_center'>
            <h2 class='hidden_on_print'>
            <?php $th=NULL; $is_thread = isset($post['tid']['count'])&&$post['tid']['count']>1;
                $is_wide = (isset($post['wide'])&&$post['wide']) || ($is_thread && isset($post['tid']['wide']) && $post['tid']['wide']);
                if($is_thread){ $th = $post['tid'];?> <?=$this->T('话题')?> <?php }else{ ?> <?=$this->T('详细')?>
            <?php } ?></h2>
            <?php $cat = NULL; 
            if($is_thread) { if(isset($th['categories']) && isset($th['categories'][0])){ $cat = $th['categories']; } }
            else { if(isset($post['categories']) && isset($post['categories'][0])) { $cat = $post['categories']; } }
            $this->MakePostTools();
            if($cat){ ?>
                <p class='hidden_on_print'><b><?=$this->T('分类')?></b> | <?php foreach($cat as $c){ 
                    echo "<a href='?category=".$c."'>".($c=='none'?$this->T('未分类'):$this->T($c))."</a> "; } ?></p>
            <?php } ?>
            <ul<?=$is_wide?"":" class='print_column'"?>>
            <?php $is_reversed=false;
                if($is_thread){
                    $use_arr = $th['arr']; $is_reversed=$this->IsReversedThread($th); $hinted=false; if($is_reversed){
                        $use_arr=array_reverse($th['arr']); $fp=array_pop($use_arr); array_unshift($use_arr,$fp); }
                    $last_end_wide = true;
                    foreach($use_arr as &$p){
                        $use_class = ($p == $post)?'focused_post':'';
                        if($last_end_wide){$use_class.=" last_wide";}
                        $show_link = ($p == $post)?false:true;
                        $make_title = ($p == $post);
                        $this->MakeSinglePost($p,$show_link,false,$use_class,false, false, false, true, false, false);
                        if($make_title){?>
                        <script>
                        document.title+=" | <?=addslashes(preg_replace('/\r|\n/u', ' ', mb_substr(strip_tags($p['html']),0,1000)))?>";
                        </script>
                        <?php } if($is_reversed && !$hinted){ ?>
                            <li class='gray smaller bold' style='text-align:center;'>
                                <p>&nbsp;</p><hr><?=$this->T('该话题最新帖子在前')?><hr><p>&nbsp;</p></li>
                        <?php $hinted = true; 
                        if($this->LoggedIn && (!$this->InHereMode)){ ?>
                            <div class='post_width_big hidden_on_print'>
                                <?php $this->MakePostingFields($is_thread?$th['last']['id']:$post['id'], true);?></div><br />
                        <?php } }
                        $last_end_wide = isset($p['end_wide']) && $p['end_wide'];
                    }
                }else{
                    $this->MakeSinglePost($post,false, false, 'focused_post',false, false, false, true, false, false);
                    ?><script>
                    document.title+=" | <?=addslashes(preg_replace('/\r|\n/u', ' ', mb_substr(strip_tags($post['html']),0,1000)))?>";
                    </script><?php
                } $class_add=""; if(isset($post['tid']['first']['no_time'])){ $class_add=' hidden_on_print'; } ?>
                <div class='smaller align_right<?=$class_add;?>'>
                    <span class='gray'><?=$this->ReadableTime($post['tid']['first']['id']);?></span>
                    <br class='hidden_on_desktop' /><?=$this->ReadableTime(isset($post['tid']['last']['version'])?
                        $post['tid']['last']['version']:$post['tid']['last']['id']);?>
                </div>
                <?php if(!$is_reversed && ($this->LoggedIn && (!$this->InHereMode))){ ?>
                    <div class='post_width_big hidden_on_print'>
                        <br /><?php $this->MakePostingFields($is_thread?$th['last']['id']:$post['id'], true);?>
                    </div>
                <?php }
                $this->MakeCommentSection($post);
                ?>
            </ul>
        </div>
    <?php
    }
    function MakePostSectionExp(&$post){
        $this->Anchors = []; $is_thread = isset($post['tid']);
        ?>
        <div class='center_exp' id='div_center'>
            <ul>
            <?php $is_reversed=false;
                if($is_thread){ $th = &$post['tid'];$use_arr = $th['arr']; $is_reversed=$this->IsReversedThread($th);
                    $hinted=false; if($is_reversed){
                        $use_arr=array_reverse($th['arr']); $fp=array_pop($use_arr); array_unshift($use_arr,$fp); }
                    foreach($use_arr as &$p){
                        $this->MakeSinglePostExp($p);
                        if($p == $th['first']){?>
                        <script>
                        document.title+=" | <?=addslashes(preg_replace('/\r|\n/u', ' ', mb_substr(strip_tags($p['html']),0,1000)))?>";
                        </script>
                        <?php } if($is_reversed && !$hinted){ ?>
                            <li class='gray smaller bold' style='text-align:center;'>
                                <p>&nbsp;</p><hr><?=$this->T('该话题最新帖子在前')?><hr><p>&nbsp;</p></li>
                        <?php $hinted = true; }
                    }
                }else{
                    $this->MakeSinglePostExp($post);
                    ?><!--<script>
                    document.title+=" | <?=addslashes(preg_replace('/\r|\n/u', ' ', mb_substr(strip_tags($post['html']),0,1000)))?>";
                    </script>--><?php
                } ?>
            </ul>
        </div>
    <?php
    }
    function MakeHereSection(&$im){ $image_okay = (isset($im) && $im!=$this->NULL_IMAGE); ?>
        <div class='here_image_box clean_a'>
            <div style='display:flex;align-items:center;height:100%;justify-content:center;width:100%;'>
                <?=$image_okay?$this->T('请稍候'):$this->T('未找到图像')?></div>
            <?php if($image_okay){ ?>
                <?php if(isset($im['video']) && isset($im['video'])!=""){ ?>
                    <video controls><source src='<?=$im['file'];?>' type='<?=$im['video']?>'></video>
                <?php }else{ ?>
                    <img id='big_image' onload="HideWaitingBar();" src="<?=$im['file'];?>" />
                <?php } ?>
            <?php } $this->MakeHereButtons($im, false); ?>
        </div>
    <?php }
    
    function MakeSideGalleryCode(){?>
        <div>
            <h3><?=$this->T('点击图片以插入：')?></h3>
            <select id="side_gallery_select" onchange="RefreshSideGallery()">
                <option value="main"><?=$this->T('全部')?></option>
                <?php if($this->LoggedIn){ ?>
                    <option value="trash"><?=$this->T('垃圾桶')?></option>
                <?php }?>
                <?php if(isset($this->Galleries[0])) foreach($this->Galleries as $g){ ?>
                    <option value="<?=$g['name']?>"><?=$g['name']?></option>
                <?php } ?>
            </select>
            <div id='side_gallery'>
            </div>
        </div>
        <script>
            function InsertImage(imgname){
                t = document.querySelector("#post_content");
                v = t.value;
                t.value = v.slice(0, t.selectionStart) + "![<?=$this->T('图片')?>](images/"+imgname+")"+ v.slice(t.selectionEnd);
                la_auto_grow(t);
            }
            function RefreshSideGallery(){
                let xhr = new XMLHttpRequest();
                xhr.onreadystatechange = (function(){
                    if (this.readyState === this.DONE) {
                        if (this.status === 200) {
                            ind = document.querySelector("#side_gallery");
                            if(res = xhr.responseText.matchAll(/\[(.*?),(.*?)\]/gu)){
                                str = "<ul class='side_thumb'>"
                                for (const m of res) {
                                    imgstr = "<img src='"+m[2]+"' />";
                                    if(m[2].match('\.mp4')){
                                        imgstr = "<video><source src='"+m[2]+"'></video>";
                                    }
                                    str+="<li><div class='file_thumb' onclick='InsertImage(\""+m[1]+"\")'>"+imgstr+"</div>"
                                }
                                str += "</ul>"
                                ind.innerHTML = str;
                            }
                        }
                    }
                });
                xhr.open("GET", "?image_list="+document.querySelector("#side_gallery_select").value, true);
                xhr.send();
            }
        </script>
    <?php
    }
    
    function MakeUploader($is_side=false){ ?>
        <div id='upload_operation_area' class='hidden_on_print'>
            <p><?=$this->T('选择、粘贴或者拖动到页面以上传图片。')?></p>
            <input type="file" id='upload_selector' accept="image/x-png,image/png,image/gif,image/jpeg" multiple/>
            <ul id='file_list'>
            </ul>
            <div class='smaller gray' id='upload_hint'><?=$this->T('就绪')?></div>
        </div>
        <a id='upload_click' class='hidden_on_print pointer' onclick='this.removeAttribute("onclick");UploadList();'
            <?=$is_side?" data-is-side='true'":""?>><?=$this->T('上传列表中的文件')?></a>
        <script>
            function pastehandler(event){
                var items = (event.clipboardData || event.originalEvent.clipboardData).items;
                for (index in items) {
                    var item = items[index];
                    if (item.kind === 'file') {
                        put = document.querySelector("#_uploader");
                        if(put) ShowSideUploader();
                        var blob = item.getAsFile();
                        AddImageFile(blob);
                        return;
                    }
                }
            }
            let _fd_list = []; var uploading=0;
            let hint=document.querySelector('#upload_hint');
            function ToggleCompress(button){
                li = button.parentNode;
                num=li.dataset.number;
                for(i=0;i<_fd_list.length;i++){
                    if (_fd_list[i][0] == num){
                        state = _fd_list[i][2];
                        if(state){_fd_list[i][2] = 0; button.innerHTML='1920';break;}
                        else{_fd_list[i][2] = 1; button.innerHTML='800';break;}
                    }
                }
            }
            function RemoveFromUpload(button){
                li = button.parentNode;
                num=li.dataset.number;
                for(i=0;i<_fd_list.length;i++){
                    if (_fd_list[i][0] == num){
                        _fd_list.splice(i, 1);break;
                    }
                }
                li.parentNode.removeChild(li);
            }
            function EndThisUpload(){
                unfinished = 0;
                for(i=0;i<_fd_list.length;i++){
                    if (_fd_list[i][3]==0){
                        unfinished=1;
                    }
                }
                if(!unfinished){
                    uploading=0;
                    hint.innerHTML="<?=$this->T('上传完成。')?>";
                    cl=document.querySelector('#upload_click');
                    if(!cl.dataset.isSide){
                        cl.innerHTML="<?=$this->T('刷新页面')?>";
                        cl.href=window.location.href;
                    }else{
                        _fd_list.splice(0,_fd_list.length);
                        fl = document.querySelector("#file_list");
                        fl.innerHTML = "";
                        list=document.querySelector('#upload_operation_area');
                        list.style.pointerEvents='';
                        list.style.opacity='';
                        cl.onclick=function(){this.removeAttribute("onclick");UploadList();};
                        document.onpaste=pastehandler;
                        RefreshSideGallery();
                    }
                }
            }
            function UploadList(){
                if(!_fd_list.length || uploading) return;
                uploading=1;
                hint.innerHTML="<?=$this->T('正在上传...')?>";
                list=document.querySelector('#upload_operation_area');
                list.style.pointerEvents='none';
                list.style.opacity='0.5';
                document.onpaste="";
                for(i=0;i<_fd_list.length;i++){
                    let xhr = new XMLHttpRequest();
                    var li = list.querySelector('[data-number="'+_fd_list[i][0].toString()+'"]')
                    xhr.open("POST", "?compress="+_fd_list[i][2].toString(), true);
                    function wrap(li, i){
                        var ind = li.querySelector('._compress_toggle')
                        return function(){
                            if (this.readyState === this.DONE) {
                                if (this.status === 200) {
                                    var response = xhr.responseText;
                                    if(res = response.match(/<uploaded>(.*)<\/uploaded>/)){
                                        ind.innerHTML = "<?=$this->T('已上传为')?> "+res[1];
                                        _fd_list[i][3] = 1;
                                    }else{
                                        ind.innerHTML = "<?=$this->T('出现错误。')?>";
                                        _fd_list[i][3] = 1;
                                    }
                                    EndThisUpload();
                                }
                            }
                        }
                    }
                    xhr.onreadystatechange = wrap(li, i);
                    xhr.send(_fd_list[i][1]);
                }
            }
            function AddImageFile(blob){
                var ext="";
                if(blob.name.match(/png$/))ext = 'png';
                else if(blob.name.match(/jpg$/))ext = 'jpg';
                else if(blob.name.match(/jpeg$/))ext = 'jpeg';
                else if(blob.name.match(/gif$/))ext = 'gif';
                else if(blob.name.match(/mp4$/))ext = 'mp4';
                else  return;
                var fd = new FormData();
                blob.name = blob.name=generateUID().toString();
                fd.append("upload_file_name", blob, "_upload_"+blob.name+"."+ext);
                _fd_list.push([blob.name, fd, 1, 0]);/* number original is_compress uploaded */
                var reader = new FileReader();
                reader.onload = function(event){
                    fl = document.querySelector("#file_list");
                    ht = "<li id='_upload_"+blob.name+"' data-number='"+blob.name+"'>"+
                         "<a class='_remove_file pointer invert_a' onclick='RemoveFromUpload(this)'>×</a> "+"<div class='file_thumb'>"+
                         "<img class='no_pop' src='"+event.target.result+"'>"+"</div>"+
                         " →<a class='_compress_toggle pointer' onclick='ToggleCompress(this)'>800</a></li>";
                    fl.innerHTML+=ht;
                };
                reader.readAsDataURL(blob);
            }
            sel = document.querySelector('#upload_selector');
            sel.addEventListener("change", function(){
                var input = this;
                function ff(file){
                    return function(e){
                        let blob = new Blob([new Uint8Array(e.target.result)], {type: file.type});
                        blob.name = file.name;
                        AddImageFile(blob);
                    }
                }
                for(i=0;i<input.files.length;i++){
                    if(input.files[i].size){
                        let reader = new FileReader();
                        reader.onload = ff(input.files[i]);
                        reader.readAsArrayBuffer(input.files[i]);
                    }
                }
            });
            function generateUID() {
                var firstPart = (Math.random() * 46656) | 0;
                var secondPart = (Math.random() * 46656) | 0;
                firstPart = ("000" + firstPart.toString(36)).slice(-3);
                secondPart = ("000" + secondPart.toString(36)).slice(-3);
                return firstPart + secondPart;
            }
            document.onpaste = pastehandler;
            function dropHandler(ev) {
              put = document.querySelector("#_uploader");
              if(put) ShowSideUploader();
              bkg=document.querySelector('#dropping_background');
              bkg.style.display="none";
              console.log('File(s) dropped');
              ev.preventDefault();ev.stopPropagation();
              if (ev.dataTransfer && ev.dataTransfer.items) {
                for (var i = 0; i < ev.dataTransfer.items.length; i++) {
                  if (ev.dataTransfer.items[i].kind === 'file') {
                    var file = ev.dataTransfer.items[i].getAsFile();
                    function ff(file){
                        return function(e){
                            let blob = new Blob([new Uint8Array(e.target.result)], {type: file.type});
                            blob.name = file.name;
                            AddImageFile(blob);
                        }
                    }
                    if(file){
                        let reader = new FileReader();
                        reader.onload = ff(file);
                        reader.readAsArrayBuffer(file);
                    }
                  }
                }
              } else {
                for (var i = 0; i < ev.dataTransfer.files.length; i++) {
                    function ff(file){
                        return function(e){
                            let blob = new Blob([new Uint8Array(e.target.result)], {type: file.type});
                            blob.name = file.name;
                            AddImageFile(blob);
                        }
                    }
                    if(ev.dataTransfer.files[i].size){
                        let reader = new FileReader();
                        reader.onload = ff(ev.dataTransfer.files[i]);
                        reader.readAsArrayBuffer(ev.dataTransfer.files[i]);
                    }
                }
              }
            }
            function dragOverHandler(ev) {
                bkg=document.querySelector('#dropping_background');
                bkg.style.display="";
                console.log('File(s) in drop zone');
                ev.preventDefault();
            }
        </script>
    <?php
    }
    
    function CanShowGallery(&$g){
        if((!$this->LoggedIn&&!$this->InExperimentalMode) && isset($g['experimental']) && $g['experimental']) return false;
        return true;
    }
    function CanShowImage(&$im){
        if((!$this->LoggedIn&&!$this->InExperimentalMode) && isset($im['galleries']) && isset($im['galleries'][0])) foreach($im['galleries'] as $ga){
            if(($g=$this->GetGallery($ga)) && isset($g['experimental']) && $g['experimental']) return false;
        }
        if(isset($this->WayBack) && substr($im['name'],0,14)>$this->WayBack) return false;
        return true;
    }
    
    function MakeGallerySection(){
        if(!isset($_GET['gallery'])) return; 
        $name=NULL; if($_GET['gallery']!='main' && $_GET['gallery']!='trash'){
            $name=$_GET['gallery'];}?>
        <script>
        document.title+=" | <?=$this->T('画廊')?>";
        </script>
        <div class='center_wide' id='div_center' style='position:relative;'>
            <h2><?=(isset($name) && ($gal=$this->GetGallery($name))!=NULL)?
                                    ("<span class='gray album_hint'>".$this->T('相册').":</span>".$this->T($name)):
                                    ($_GET['gallery']=='trash'?$this->T('垃圾桶'):$this->T('画廊'))?></h2>
            <div class='hidden_on_desktop'>
                <?=$this->T('前往')?>
                <select id="gallery_go_to" onchange="window.location.href='?gallery='+this.value;">
                <?php if(isset($this->Galleries[0])) foreach($this->Galleries as $g){
                    if(!isset($g['featured']) || !$g['featured'] ||
                        !$this->CanShowGallery($g)){ continue; } $is_this = ($_GET['gallery']==$g['name']);?>
                        <option value="<?=$g['name']?>" <?=$is_this?"selected":""?>>
                            <?=(isset($g['experimental'])&&$g['experimental'])?'E ':''?><?=$this->T($g['name'])?></option>
                <?php } ?>
                <option value="main" <?=$_GET['gallery']=='main'?"selected":""?>>[<?=$this->T('全部')?>]</option>
                <?php if($this->LoggedIn){ ?>
                    <option value="trash" <?=$_GET['gallery']=='trash'?"selected":""?>>[<?=$this->T('垃圾桶')?>]</option>
                <?php } ?>
                <?php if(isset($this->Galleries[0])) foreach($this->Galleries as $g){
                    if((isset($g['featured']) && $g['featured']) ||
                        !$this->CanShowGallery($g)){ continue; } $is_this = ($_GET['gallery']==$g['name']);?>
                        <option value="<?=$g['name']?>" <?=$is_this?"selected":""?>>
                            <?=(isset($g['experimental'])&&$g['experimental'])?'E ':''?><?=$this->T($g['name'])?></option>
                <?php } ?>
                </select>
            </div>
            <?php if($this->LoggedIn){?>
                <div>
                    <?php if(isset($name)){ ?>
                        <div style='text-align:right;position:absolute;right:0;top:0;width:100%;' class='invert_a smaller hidden_on_print'>
                            <a href='javascript:ShowDeleteMenu();'><?=$this->T('删除相册')?></a><br />
                            <?php if(isset($gal['featured']) && $gal['featured']!=false){ ?>
                                <a href='?gallery=<?=$_GET['gallery']?>&gallery_set_featured=<?=$_GET['gallery']?>&value=false'>
                                    <?=$this->T('取消精选')?></a>
                            <?php }else{ ?>
                                <a href='?gallery=<?=$_GET['gallery']?>&gallery_set_featured=<?=$_GET['gallery']?>&value=true'>
                                    <?=$this->T('设为精选')?></a>
                            <?php } ?><br />
                            <?php if(isset($gal['experimental']) && $gal['experimental']!=false){ ?>
                                <a href='?gallery=<?=$_GET['gallery']?>&gallery_set_experimental=<?=$_GET['gallery']?>&value=false'>
                                    <?=$this->T('取消实验')?></a>
                            <?php }else{ ?>
                                <a href='?gallery=<?=$_GET['gallery']?>&gallery_set_experimental=<?=$_GET['gallery']?>&value=true'>
                                    <?=$this->T('设为实验')?></a>
                            <?php } ?>
                            <div class='pop_menu invert_a' id='gallery_delete_menu' style='display:none;'>
                                <div style='float:left;' class='gray'><?=$this->T('该操作不删除图片。')?></div>
                                <a href='javascript:HidePopMenu();'>×</a>
                                <hr />
                                <a href='?gallery=main&gallery_edit_delete=<?=$_GET['gallery']?>'><b><?=$this->T('删除相册')?></b></a>
                            </div>
                        </div>
                    <?php } ?>
                    <?php $this->MakeUploader(false); ?>
                    <div style='text-align:right;position:relative;' class='invert_a smaller hidden_on_print'>
                        <div style='position:relative'>
                        <?php if(isset($name)){ ?>
                            <a href='javascript:ShowGalleryEditMenu("<?=$name?>")'><?=$this->T('改名')?></a>
                        <?php } ?>
                            <a href='javascript:ShowGalleryEditMenu(null)'><?=$this->T('添加')?></a>
                            <div class='pop_menu invert_a' id='gallery_edit_menu' style='display:none;max-width:90%;'>
                                <form action="<?=$_SERVER['REQUEST_URI']?>&edit_gallery=true"
                                    method="post" style='display:none;' id='gallery_edit_form'></form>
                                <a style='float:left;'><?=$this->T('相册名字：')?></a>
                                <a href='javascript:HidePopMenu();'>×</a>
                                <input type='text' form='gallery_edit_form' name='gallery_edit_new_name' id='gallery_edit_new_name' style='width:10em;'>
                                <input type='text' form='gallery_edit_form' name='gallery_edit_old_name' id='gallery_edit_old_name' style='display:none'>
                                <input class='button' type='submit' form='gallery_edit_form'
                                    name='gallery_edit_confirm' id='gallery_edit_confirm' value='<?=$this->T('确认')?>'>
                            </div>
                        </div>
                    </div>
                    <div class='smaller hidden_on_print'>
                        <form action="<?=$_SERVER['REQUEST_URI']?>"
                            method="post" style='display:none;' id='gallery_move_form'></form>
                        <input type='text' form='gallery_move_form' name='gallery_move_ops'
                            id='gallery_move_ops' style='display:none'>
                        <p><?=$this->T('选择了')?> <span id='gallery_selected_count'>0</span> <?=$this->T('个图片。')?>
                            <a href='javascript:ClearSelectedImages()'><?=$this->T('清除')?></a></p>
                        <p><?=$this->T('添加到')?>
                            <select id="gallery_move_to">
                            <option value="trash"><?=$this->T('垃圾桶')?></option>
                            <?php if(isset($this->Galleries[0])) foreach($this->Galleries as $g){ ?>
                                <option value="<?=$g['name']?>"><?=$this->T($g['name'])?></option>
                            <?php } ?>
                            </select>
                            <a href='javascript:AddToGallery()'><?=$this->T('执行')?></a>
                        </p>
                        <p>
                            <?=$this->T('或者')?><a href='javascript:RemoveFromGallery()'><?=$this->T('从相册移除')?></a>
                        </p>
                    </div>
                </div>
            <?php } ?>
            <p>&nbsp;</p>
            <div>
                <?php $opened=0; $prev_year=""; if(isset($this->Images[0])) foreach($this->Images as $im){
                    if(!$this->CanShowImage($im)){ continue; } 
                    if($_GET['gallery']=='trash') $name='trash';
                    if($_GET['gallery']!='main'){ if(!isset($im['galleries']) || !in_array($name, $im['galleries'])) continue;}
                    $year = substr($im['name'], 0, 4);
                    if($year!=$prev_year){
                        if($opened) { ?><div class='p_thumb' style='flex-grow:10000;box-shadow:none;height:0;'></div></div></div><?php } ?>
                        <div><h2 class='sticky_title'><?=$year;?></h2><div class='p_row'><?php $prev_year=$year; $opened=1; } ?>
                    <div class='p_thumb<?=isset($im['parent'])?" p_thumb_narrow":""?>'>
                        <?php if($this->LoggedIn){ ?>
                            <div class="post_menu_button _select_hook white" onclick='ToggleSelectImage(this, "<?=$im["name"]?>")'>●</div>
                        <?php } ?>
                        <a href='?show_image=<?=$im['name']?>' target='_blank' onclick='event.preventDefault();'>
                            <?php if(isset($im['video']) && $im['video']!=""){ ?>
                                <video data-imgsrc='<?=$im["name"]?>'><source src='<?=$im['file']?>' type='<?=$im['video']?>'></video>
                            <?php }else{ ?>
                            <img src='<?=$im['thumb']?>' data-imgsrc='<?=$im["name"]?>'
                                <?=isset($im['product'])?'data-product="'.$im["product"].'"':""?>
                                <?=isset($im['parent'])?'data-parent="'.$im["parent"].'"':""?>/>
                            <?php } ?>
                        </a>
                    </div>
                <?php } if($opened) { ?>
                <div class='p_thumb' style='flex-grow:10000;box-shadow:none;height:0;'></div></div><?php } ?>
            </div>
        </div>
        <?php if($this->LoggedIn){ ?>
            <script type='text/javascript'>
            var selected_image = new Array();
            function RemoveFromGallery(){
                form = document.querySelector('#gallery_move_form');
                ops = document.querySelector('#gallery_move_ops');
                sel = document.querySelector('#gallery_move_to');
                ops.value = "REM <?=$_GET['gallery']?>"+" "+selected_image.join(' ');
                form.submit();
            }
            function AddToGallery(){
                form = document.querySelector('#gallery_move_form');
                ops = document.querySelector('#gallery_move_ops');
                sel = document.querySelector('#gallery_move_to');
                ops.value = "ADD "+sel.value+" "+selected_image.join(' ');
                form.submit();
            }
            function ClearSelectedImages(){
                selected_image.splice(0, selected_image.length);
                checks = document.querySelectorAll('._select_hook');
                [].forEach.call(checks, function(c){
                    c.classList.remove('p_thumb_selected');
                });
                count = document.querySelector('#gallery_selected_count');
                count.innerHTML="0";
            }
            function ToggleSelectImage(elem, name){
                count = document.querySelector('#gallery_selected_count');
                if(selected_image.indexOf(name) == -1){ selected_image.push(name); elem.classList.add('p_thumb_selected'); }
                else{ selected_image.splice(selected_image.indexOf(name), 1); elem.classList.remove('p_thumb_selected'); }
                count.innerHTML=selected_image.length.toString()
            }
            function ShowGalleryEditMenu(old_name){
                m = document.querySelector('#gallery_edit_menu');
                old = document.querySelector('#gallery_edit_old_name');
                newname = document.querySelector('#gallery_edit_new_name');
                m.style.display='block';
                if(old_name!=''){ old.value=old_name; newname.value=old.value; }else{ old.value=''; }
            }
            function ShowDeleteMenu(){
                m=document.querySelector('#gallery_delete_menu');
                m.style.display='block';
            }
            </script>
        <?php } ?>
    <?php
    }
    
    function MakeGalleryLeft(){?>
        <div class='left hidden_on_mobile gallery_left' id='div_left'>
            <h2><?=$this->T('相册')?></h2>
            <div>
                <span class='gray smaller bold'><?=$this->T('精选')?><hr>  </span>
                <ul>
                    <?php if(isset($this->Galleries[0])) foreach($this->Galleries as $g){ 
                        if(!isset($g['featured']) || !$g['featured'] || !$this->CanShowGallery($g)){ continue; } ?>
                        <a href='?gallery=<?=$g['name']?>' onclick='ShowWaitingBar()'>
                            <li class='<?=$_GET['gallery']==$g['name']?'selected':""?>'>
                                <?=(isset($g['experimental'])&&$g['experimental'])?'E ':''?><?=$this->T($g['name'])?></li></a>
                    <?php } ?>
                </ul>
                <div class='smaller'><span class='gray'><?=$this->T('其他相册')?><hr></span><ul>
                    <a href='?gallery=main' onclick='ShowWaitingBar()'>
                        <li class='<?=$_GET['gallery']=='main'?' selected':""?>'>[<?=$this->T('全部图片')?>]</li></a>
                    <?php if(isset($this->Galleries[0])) foreach($this->Galleries as $g){ 
                        if((isset($g['featured']) && $g['featured']) || !$this->CanShowGallery($g)){ continue; } ?>
                        <a href='?gallery=<?=$g['name']?>' onclick='ShowWaitingBar()'>
                            <li class='<?=$_GET['gallery']==$g['name']?' selected':""?>'>
                                <?=(isset($g['experimental'])&&$g['experimental'])?'E ':''?><?=$this->T($g['name'])?></li></a>
                    <?php } ?>
                    <?php if($this->LoggedIn){ ?>
                        <a href='?gallery=trash' onclick='ShowWaitingBar()'>
                            <li class='<?=$_GET['gallery']=='trash'?' selected':""?>'>[<?=$this->T('垃圾桶')?>]</li></a>
                    <?php } ?>
                </ul></div>
                <p>&nbsp;</p>
                <script>
                </script>
            </div>
        </div>
    <?php
    }
    
    function MakeTOC(){?>
        <div class='right hidden_on_mobile' id='div_right'>
            <?php if(isset($this->Anchors[0])){?>
                <h2><?=$this->T('目录')?></h2><ul>
                <?php
                    foreach($this->Anchors as $a){?>
                        <li class='toc_entry_<?=$a[0]>5?5:$a[0]?>'><a href='#<?=$a[1]?>'>
                            <?=$this->T($this->ChoosePartsByLanguage($a[2]))?></a></li>
                    <?php }
                ?></ul>
            <?php }else{ ?>
                <h2 class='gray'><?=$this->T('目录')?></h2>
                <span class='gray smaller'><?=$this->T('未找到目录')?></span>
            <?php } ?>
        </div>
    <?php
    }
    
    
    function MakeSettings(){?>
        <div class='settings'>
            <h2><?=$this->T('设置')?></h2>
            <table style='white-space:nowrap;'>
                <?php if($this->LoggedIn){ ?>
                    <form action="index.php?settings=true" method="post" style='display:none;' id='settings_form'></form>
                    <colgroup><col style='min-width:5em;'><col style='width:100%;min-width:5em;'></colgroup>
                    <thead><tr><td><?=$this->T('选项')?></td><td><?=$this->T('值')?></td></tr></thead>
                    <tr><td><?=$this->T('网站标题')?></td>
                        <td><input type="text" form="settings_form" id='settings_title' name='settings_title'
                            value='<?=$this->Title?>'/></td></tr>
                    <tr><td><?=$this->T('短标题')?></td>
                        <td><input type="text" form="settings_form" id='settings_short_title' name='settings_short_title'
                            value='<?=$this->ShortTitle?>'/></td></tr>
                    <tr><td><?=$this->T('显示名称')?></td>
                        <td><input type="text" form="settings_form" id='settings_display_name' name='settings_display_name'
                        value='<?=$this->DisplayName?>'/></td></tr>
                    <tr><td><?=$this->T('电子邮件')?></td>
                        <td><input type="text" form="settings_form" id='settings_email' name='settings_email'
                        value='<?=$this->EMail?>'/></td></tr>
                    <tr><td><?=$this->T('导航栏')?>
                        <?=isset($this->SpecialNavigation)?"<a href='?post=".$this->SpecialNavigation."'>→</a>":""?></td>
                        <td><input type="text" form="settings_form" id='settings_special_navigation' name='settings_special_navigation'
                        value='<?=$this->SpecialNavigation?>'/></td></tr>
                    <tr><td><?=$this->T('脚注')?> 1<?=isset($this->SpecialFooter)?"<a href='?post=".$this->SpecialFooter."'>→</a>":""?></td>
                        <td><input type="text" form="settings_form" id='settings_special_footer' name='settings_special_footer'
                        value='<?=$this->SpecialFooter?>'/></td></tr>
                    <tr><td><?=$this->T('脚注')?> 2<?=isset($this->SpecialFooter2)?"<a href='?post=".$this->SpecialFooter2."'>→</a>":""?></td>
                        <td><input type="text" form="settings_form" id='settings_special_footer2' name='settings_special_footer2'
                        value='<?=$this->SpecialFooter2?>'/></td></tr>
                    <tr><td><?=$this->T('置顶文')?><?=isset($this->SpecialPinned)?"<a href='?post=".$this->SpecialPinned."'>→</a>":""?></td>
                        <td><input type="text" form="settings_form" id='settings_special_pinned' name='settings_special_pinned'
                        value='<?=$this->SpecialPinned?>'/></td></tr>
                    <tr><td><?=$this->T('默认相册')?></td>
                        <td><input type="text" form="settings_form" id='settings_default_gallery' name='settings_default_gallery'
                        value='<?=$this->DefaultGallery?>'/></td></tr>
                    <tr><td><?=$this->T('SelfAuth 路径')?></td>
                        <td><input type="text" form="settings_form" id='settings_selfauth_path' name='settings_selfauth_path'
                        value='<?=$this->SelfAuthPath?>'/></td></tr>
                    <tr><td><?=$this->T('启用评论')?></td>
                        <td><input type="checkbox" id="settings_enable_comments" name="settings_enable_comments"
                        form="settings_form" <?=$this->CommentEnabled?"checked":""?>/></td></tr>
                    <tr><td><?=$this->T('附加操作')?></td><td><a class='gray' href='index.php?extras=true'><?=$this->T('进入')?></a></td></tr>
                        
                    <tr><td class='smaller gray'>&nbsp;</td></tr>
                    <tr><td class='smaller gray'><?=$this->T('长毛象')?></td></tr>
                    <tr><td><?=$this->T('长毛象实例')?></td>
                        <td><input type="text" form="settings_form" id='settings_mastodon_url' name='settings_mastodon_url'
                        value='<?=$this->MastodonURL?>'/></td></tr>
                    <tr><td><?=$this->T('长毛象令牌')?></td>
                        <td><input type="text" form="settings_form" id='settings_mastodon_token' name='settings_mastodon_token'
                        value='<?=$this->MastodonToken?>'/></td></tr>
                    <tr><td><?=$this->T('偏好语言')?></td>
                        <td><input type="text" form="settings_form" id='settings_mastodon_lang' name='settings_mastodon_lang'
                        value='<?=$this->MastodonPreferredLang?>'/></td></tr>
                    <tr><td><?=$this->T('本站地址')?></td>
                        <td><input type="text" form="settings_form" id='settings_host_url' name='settings_host_url'
                        value='<?=$this->HostURL?>'/></td></tr>
                    
                    <tr><td class='smaller gray'>&nbsp;</td></tr>
                    <tr><td class='smaller gray'><?=$this->T('Activity Pub')?></td></tr>
                    <tr><td><?=$this->T('用户名')?></td>
                        <td><input type="text" form="settings_form" id='settings_apub_id' name='settings_apub_id'
                        value='<?=$this->APubID?>'/></td></tr>
                        
                    <tr><td class='smaller gray'>&nbsp;</td></tr>
                    <tr><td class='smaller gray'><?=$this->T('这里访问')?></td></tr>
                    <tr><td><?=$this->T('主机')?></td>
                        <td><input type="text" form="settings_form" id='settings_here_host' name='settings_here_host'
                        value='<?=$this->HereHost?>'/></td></tr>
                    <tr><td><?=$this->T('网站标题')?></td>
                        <td><input type="text" form="settings_form" id='settings_here_title' name='settings_here_title'
                            value='<?=$this->HereTitle?>'/></td></tr>
                    <tr><td><?=$this->T('短标题')?></td>
                        <td><input type="text" form="settings_form" id='settings_here_short_title' name='settings_here_short_title'
                            value='<?=$this->HereShortTitle?>'/></td></tr>
                    <tr><td><?=$this->T('相册')?></td>
                        <td><input type="text" form="settings_form" id='settings_here_album' name='settings_here_album'
                        value='<?=$this->HereAlbum?>'/></td></tr>
                    <tr><td><?=$this->T('导航栏')?>
                        <?=isset($this->HereNavigation)?"<a href='?post=".$this->HereNavigation."'>→</a>":""?></td>
                        <td><input type="text" form="settings_form" id='settings_here_navigation' name='settings_here_navigation'
                        value='<?=$this->HereNavigation?>'/></td></tr>
                    <tr><td><?=$this->T('脚注')?><?=isset($this->HereFooter)?"<a href='?post=".$this->HereFooter."'>→</a>":""?></td>
                        <td><input type="text" form="settings_form" id='settings_here_footer' name='settings_here_footer'
                        value='<?=$this->HereFooter?>'/></td></tr>
                        
                    <tr><td class='smaller gray'>&nbsp;</td></tr>
                    <tr><td class='smaller gray'><?=$this->T('实验访问')?></td></tr>
                    <tr><td><?=$this->T('主机')?></td>
                        <td><input type="text" form="settings_form" id='settings_exp_host' name='settings_exp_host'
                        value='<?=$this->ExpHost?>'/></td></tr>
                    <tr><td><?=$this->T('网站标题')?></td>
                        <td><input type="text" form="settings_form" id='settings_exp_title' name='settings_exp_title'
                            value='<?=$this->ExpTitle?>'/></td></tr>
                    <tr><td><?=$this->T('短标题')?></td>
                        <td><input type="text" form="settings_form" id='settings_exp_short_title' name='settings_exp_short_title'
                            value='<?=$this->ExpShortTitle?>'/></td></tr>
                    <tr><td><?=$this->T('首次提示')?><?=isset($this->ExpCaution)?"<a href='?post=".$this->ExpCaution."'>→</a>":""?></td>
                        <td><input type="text" form="settings_form" id='settings_exp_caution' name='settings_exp_caution'
                        value='<?=$this->ExpCaution?>'/></td></tr>
                    <tr><td><?=$this->T('相册')?></td>
                        <td><input type="text" form="settings_form" id='settings_exp_album' name='settings_exp_album'
                        value='<?=$this->ExpAlbum?>'/></td></tr>
                    <tr><td><?=$this->T('导航栏')?>
                        <?=isset($this->ExpNavigation)?"<a href='?post=".$this->ExpNavigation."'>→</a>":""?></td>
                        <td><input type="text" form="settings_form" id='settings_exp_navigation' name='settings_exp_navigation'
                        value='<?=$this->ExpNavigation?>'/></td></tr>
                    <tr><td><?=$this->T('脚注')?><?=isset($this->ExpFooter)?"<a href='?post=".$this->ExpFooter."'>→</a>":""?></td>
                        <td><input type="text" form="settings_form" id='settings_exp_footer' name='settings_exp_footer'
                        value='<?=$this->ExpFooter?>'/></td></tr>
                        
                    <tr><td class='smaller gray'>&nbsp;</td></tr>
                    <tr><td class='smaller gray'><?=$this->T('管理员')?></td><td class='smaller'>
                        <a href='index.php?logout=true'><?=$this->T('登出')?></a></td></tr>
                    <tr><td><?=$this->T('帐号')?></td>
                        <td><input type="text" form="settings_form" id='settings_id' name='settings_id'
                            value='<?=$this->Admin?>'/></td></tr>
                    <tr><td><?=$this->T('新密码')?></td>
                        <td><input type="password" form="settings_form"
                            id='settings_new_password' name='settings_new_password' /></td></tr>
                    <tr><td><?=$this->T('再次输入')?></td>
                        <td><input type="password" form="settings_form"
                            id='settings_new_password_redo' name='settings_new_password_redo' /></td></tr>
                    <tr><td><?=$this->T('旧密码')?></td>
                        <td><input type="password" form="settings_form" id='settings_old_password' name='settings_old_password' /></td></tr>
                <?php }else{ ?>
                    <form action="<?=$_SERVER['REQUEST_URI']?>" method="post" style='display:none;' id='login_form'></form>
                    <colgroup><col style='min-width:3em;'><col style='width:100%;min-width:5em;'></colgroup>
                    <tr><td class='smaller gray'><?=$this->T('请登录')?></td></tr>
                    <tr><td><?=$this->T('帐号')?></td>
                        <td><input type="text" form="login_form" id='login_id' name='login_id' /></td></tr>
                    <tr><td><?=$this->T('密码')?></td>
                        <td><input type="password" form="login_form" id='login_password' name='login_password' /></td></tr>
                <?php } ?>
            </table>
            <?php if($this->LoggedIn){ ?>
                <input class='button' form="settings_form" type="submit" name='settings_button' value='<?=$this->T('保存设置')?>' />
            <?php }else{ ?>
                <input class='button' form="login_form" type="submit" name='login_button' value='<?=$this->T('登录')?>' />
            <?php } ?>
        </div>
    <?php
    }
    
    function MakeExtraOperations(){?>
        <div class='settings' style='overflow:auto;'>
            <h2><?=$this->T('附加操作')?></h2>
            <a href='index.php?&settings=true'><?=$this->T('返回一般设置')?></a>
            <p>&nbsp;</p>
            <h3><?=$this->T('自动重定向')?></h3>
            <span class='smaller gray'>
                <?=$this->T('P为帖子跳转，匹配REQUEST_URI跳到目标文章；S为站点跳转，可以重定向来源域名，例子：')?>
            <br /><pre>P discount:20001001010101;<br />S old_domain:www.new_domain.com:20001001010101;</pre></span>
            <form action="<?=$_SERVER['REQUEST_URI']?>" method="post" style='display:none;' id='settings_form2'></form>
            <textarea id="settings_redirect" name="settings_redirect" rows="3" class='full_box' wrap="off"
                form='settings_form2'><?=$this->DisplayRedirectConfig()?></textarea>
            <input class='button' form="settings_form2" type="submit" name='settings_save_redirect'
                value='<?=$this->T('保存重定向设置')?>' />
            <p>&nbsp;</p>
            <h3><?=$this->T('自定义翻译')?></h3>
            <span class='smaller gray'>
                <?=$this->T('填写格式：')?>
            <br /><pre>- 语言 | Language</pre></span>
            <form action="<?=$_SERVER['REQUEST_URI']?>" method="post" style='display:none;' id='settings_form3'></form>
            <textarea id="settings_translation" name="settings_translation" rows="3" class='full_box' wrap="off"
                form='settings_form3'><?=$this->CustomTranslationContent?></textarea>
            <input class='button' form="settings_form3" type="submit" name='settings_save_translation'
                value='<?=$this->T('保存翻译')?>' />
            <p>&nbsp;</p>
            <p class='smaller gray'><?=$this->T('当心！下列操作将立即执行：')?></p>
            <ul>
                <li><a href='index.php?rewrite_styles=true'><?=$this->T('重新写入默认CSS')?></a></li>
                <li><a href='index.php?regenerate_thumbnails=true'><?=$this->T('重新生成图片缩略图')?></a></li>
                <li><a href='index.php?clear_all_logins=true'><?=$this->T('删除所有登录')?>(<?=sizeof($this->LoginTokens);?>)</a></li>
            </ui>
            <br /><br /><br /><a href='index.php?&settings=true'><?=$this->T('返回一般设置')?></a><br />&nbsp;
        </div>
    <?php
    }
    
    function MakeMainBegin(){?>
        <div class='main' <?php if($this->LoggedIn && (!$this->InHereMode)){ ?>
            ondrop="_dropHandler(event);" ondragover="_dragOverHandler(event);"<?php } ?>>
    <?php
    }
    function MakeMainEnd(){?>
        </div>
    <?php
    }
    
    function MakeExpFooter(){
        $exptitle = isset($this->HereDisplayTitle)?('"'.$this->T($this->ChoosePartsByLanguage($this->HereDisplayTitle)).'"'):NULL;
        if(!isset($exptitle)) $exptitle=$this->InExperimentalMode?$this->T($this->ExpTitle):$this->T($this->HereTitle);?>
        <div class='small_footer exp_h_f exp_f'><div>
            <b><?=$exptitle?></b> ©<?=$this->T($this->DisplayName)?>
            <?=isset($this->HereMainImage)?("<a class='invert_a' href='?here=images/".$this->HereMainImage['name']."'>".$this->T("看全图")."</a>"):""; ?>
            <div class='wayback_link gray clean_a invert_a hidden_on_print'><br class="hidden_on_desktop" />
            <?php if($this->InExperimentalMode && isset($this->ExpFooter) && ($p = &$this->GetPost($this->ExpFooter,true))!=NULL){
                echo $this->GenerateSinglePost($p, false, false, false, false,$this->NULL_POST,false);
            }else if($this->InHereMode && isset($this->HereFooter) && ($p = &$this->GetPost($this->HereFooter,true))!=NULL){
                echo $this->GenerateSinglePost($p, false, false, false, false,$this->NULL_POST,false);
            }?></div>
            </div>
        </div>
        </div><!-- page -->
        </body>
        </html>
        <script>
            if(trans = document.getElementById('translate_button')){
                trans.href='https://translate.google.com/translate?sl=auto&tl=en-US&u='+encodeURIComponent(document.location.href);
            }
        </script>
    <?php    
    }
    
    function MakeImageOverlay($side_info=null,$static_image=null,$static_video=null){
        $static=isset($side_info); ?>
        <div id='big_image_overlay' <?=$static?"":"style='display:none'";?>>
            <div class='big_image_box clean_a' onclick='HideBigImage(1)'>
                <div style='display:flex;align-items:center;height:100%;justify-content:center;width:100%;'><?=$this->T('请稍候')?></div>
                <?php if(!$static_video){ ?>
                <img id='big_image' onload="HideWaitingBar();" <?=isset($static_image)?("src='".$static_image."'"):""?>/><?php } ?>
                <?php if(!$static_image){ ?>
                <video id='big_video' onloadstart="HideWaitingBar();" controls><source id='big_video_src' type='video/mp4'
                    <?=isset($static_video)?("src='".$static_video."'"):""?>></video><?php } ?>
            </div>
            <div class='big_side_box' onclick='HideBigImage(1);'>
            <?php if(!$static){ ?>
            <div class='big_image_box clean_a image_nav' onclick='HideBigImage(1)'>
                <div ><a id='prev_image' class='image_nav_prev img_btn_hidden' onclick="event.stopPropagation();">
                    <div class='lr_buttons'>←</div></a></div>
                <div ><a id='next_image' class='image_nav_next img_btn_hidden' onclick="event.stopPropagation();">
                    <div class='lr_buttons'>→</div></a></div>
                <div id='here_buttons'></div>
            </div><?php } ?>
                <div class='side_box_mobile_inner'>
                    <?php if(!$static){ ?>
                    <div class='inquiry_buttons img_btn_hidden' id='inquiry_buttons' onclick="event.stopPropagation();">
                        <span style='display:none;' id='image_purchase' class='clean_a'>
                            <b><a id='image_purchase_button' target="_blank">￥<?=$this->T('购买印刷品')?></a></b>
                            <br class='hidden_on_desktop block_on_mobile' /></span>
                        <b><a class='clean_a' id='image_download'><span id='download_processing'>↓</span><?=$this->T('下载')?></a></b>
                        <?php if(isset($this->EMail) && $this->EMail!=""){ ?>
                            &nbsp;<a class='clean_a' id='image_inquiry'>
                            <b>@</b><?=$this->T('咨询')?></a>
                        <?php } ?><hr class='hidden_on_desktop block_on_mobile' />
                    </div>
                    <div id='big_image_share' class='clean_a' onclick="event.stopPropagation();">
                        <li class='wayback_close'><a onclick='HideBigImage(1);'><?=$this->T('关闭')?> ×</a></li>
                        <li><a id='big_share_copy'>⎘ <?=$this->T('复制链接')?></a></li>
                        <hr />
                    </div><?php } //static ?>
                    <div id='big_image_info' onclick="event.stopPropagation();"><?=$static?$side_info:""?></div>
                    <?php if($this->LoggedIn && !$static){ ?><div id='big_image_ops' onclick="event.stopPropagation();">
                        <br /><?=$this->T('印刷品链接')?>
                        <form action="" method="post" style='display:none;' id='image_ops_form'></form>
                        <input type='text' id='image_ops_product_link' name='image_ops_product_link' form="image_ops_form" >
                        <?=$this->T('重命名')?>
                        <input type='text' id='image_edit_new_name' name='image_edit_new_name' form="image_ops_form" >
                        <?=$this->T('主图')?>
                        <input type='text' id='image_parent' name='image_parent' form="image_ops_form" >
                        <input class='button' form="image_ops_form" type="submit" name='image_button' value=<?=$this->T('保存')?> />
                        <br /><br /><?=$this->T('替换图像')?>
                        <form action="" method="post" style='display:none;' id='image_edit_form' enctype="multipart/form-data"></form>
                        <input type="file" form='image_edit_form'
                            id='big_image_upload' name='upload_file_name' accept="image/x-png,image/png,image/gif,image/jpeg"/><br />
                        <input class='button' form="image_edit_form" type="submit" name='image_replace_button'
                            value=<?=$this->T('执行')?> />
                    </div><?php } ?>
                </div>
            </div>
        </div>
    <?php }
    
    function MakePageEnd(){?>
        </div><!-- page -->
        </body>
        </html>
    <?php }
    function MakeFooter(&$p){?>
        <div class='small_footer'><div>
            <hr />
            <?php if(isset($p['tid']['footer'])){ ?><div class='show_on_print'><?=$p['tid']['footer'];?></div><?php } ?>
            <b><?=$this->T($this->Title)?></b>
            <span onclick='event.stopPropagation()'
                ondblclick='javascript:window.location.href="index.php?settings=true"'>©</span><?=$this->T($this->DisplayName)?>
            <div class='wayback_link clean_a invert_a hidden_on_print'><?php if(!isset($this->WayBack)){ ?>
                - <a href='<?=$this->GetRedirect()?>&set_wayback=true'><?=$this->T('以过去的日期浏览')?></a>
            <?php }else{ ?><a href='<?=$this->GetRedirect()?>&set_wayback=false'><?=$this->T('回到当前日期')?></a><?php } ?>
            <br class='hidden_on_desktop' /> - <a href='<?=$this->GetRedirect()?>&toggle_font=true'>
                <?=$this->T('切换字体')?>: <?=$this->T($this->UseRemoteFont?"远程":"本地");?></a>
            <br class='hidden_on_desktop' />
                - <a rel="alternate" type="application/rss+xml" href="?rss=<?=$this->LanguageAppendix;?>" />RSS/Atom<sup><?=$this->LanguageAppendix;?></sup></a>
            </div>
            <div class='smaller show_on_print'><?=$_SERVER['SERVER_NAME']?><br /><?=$this->EMail?></div>
        </div></div>
        <div class='footer'>
            <div style='white-space:nowrap;'>
                <div class='footer_additional'>
                <?php if(isset($this->SpecialFooter) && ($p = &$this->GetPost($this->SpecialFooter))!=NULL){
                    echo $this->GenerateSinglePost($p, false, false, false, false,$this->NULL_POST,false);
                } ?>
                </div>
                <div class='footer_additional'>
                <?php if(isset($this->SpecialFooter2) && ($p = &$this->GetPost($this->SpecialFooter2))!=NULL){
                    echo $this->GenerateSinglePost($p, false, false, false, false,$this->NULL_POST,false);
                } ?>
                </div>
            </div>
        </div>
        <p>&nbsp;<p>
        <div id='dropping_background' style='display:none;' onclick='this.style.display="none";'
            ondrop="_dropHandler(event);" ondragover="_dragOverHandler(event);">
            <h2 style='width:100%;'><?=$this->T('上传到这里')?></h2>
        </div>
        <?php $this->MakeImageOverlay(); $this->MakePageEnd(); ?>
        <script>
            <?=$this->ExtraScripts?>
            if(back = document.getElementById('button_back')){
                if(document.referrer.indexOf(location.protocol + "//" + location.host) == 0){
                    back.href='javascript:history.back()';
                }else{
                    back.href='index.php';
                }
            }
            function copy_text(t) {
                ta = document.createElement('textarea');
                document.body.appendChild(ta);
                ta.value = t;
                ta.select();
                document.execCommand("copy");
                document.body.removeChild(ta);
            }
            if(trans = document.getElementById('translate_button')){
                trans.href='https://translate.google.com/translate?sl=auto&tl=en-US&u='+encodeURIComponent(document.location.href);
            }
            <?php if($this->LoggedIn){ ?>
                var Scenter = document.querySelector('#div_center');
                var Sposts = Scenter.querySelectorAll('.post');
                <?php if(isset($this->MastodonToken)){ ?>
                    function MarkPostDoneMastodon(id,mastodon_url){
                        for(var i=0;i<Sposts.length;i++){
                            if(Sposts[i].dataset.postId==id){ Sposts[i].dataset.mastodon_url= mastodon_url; break;} }
                    }
                <?php } ?>
                <?php if($this->PageType=='post' && !isset($this->WayBack)) { ?>
                    var Smerge_post_dialog = Scenter.querySelector('#merge_post_dialog');
                    var Smerge_post = Scenter.querySelector('#merge_post');
                    var Smerge_post_count = Smerge_post_dialog.querySelector('#merge_post_count');
                    var Smerge_post_btn = Scenter.querySelector('#merge_post_btn');
                    var Smerge_cancel = Scenter.querySelector('#merge_cancel');
                    var Smerge_select = Scenter.querySelector('#merge_select');
                    var Smerge_thread_dialog = Scenter.querySelector('#merge_thread_dialog');
                    var Smerge_thread = Scenter.querySelector('#merge_thread');
                    var Smerge_thread_target = Scenter.querySelector('#merge_thread_target');
                    var Smerge_thread_btn = Scenter.querySelector('#merge_thread_btn');
                    var select_mode = false; var merge_mode = false; var selected_posts = null;
                    function TogglePostSelectMode(force_on=false,force_off=false){
                        Scenter.classList.add('post_selecting');
                        if((!select_mode || force_on) && !force_off){ ToggleThreadMerge(false,true); Scenter.classList.add('post_selecting');
                            Smerge_post_dialog.style.display="block";Smerge_post_btn.classList.add('text_highlight');
                            Smerge_cancel.innerText='<?=$this->T("取消")?>'; select_mode = 1;}
                        else{ select_mode = 0; Scenter.classList.remove('post_selecting');
                            Smerge_post_dialog.style.display="none";Smerge_post_btn.classList.remove('text_highlight');
                            Smerge_cancel.innerText='<?=$this->T("工具")?>';Smerge_select.selectedIndex='0'; }
                        for(var i=0;i<Sposts.length;i++){ Sposts[i].classList.remove('post_selected');
                            Sposts[i].onclick=select_mode?function(){TogglePostSelect(this);}:null; }
                        Smerge_post_count.innerText="0";
                        Smerge_post.href="#";
                    }
                    function TogglePostSelect(elem){
                        if(elem.classList.contains('post_selected')){ elem.classList.remove('post_selected'); }
                        else{ elem.classList.add('post_selected'); }
                        selected_posts = new Array();
                        for(var i=0;i<Sposts.length;i++){ if(Sposts[i].classList.contains('post_selected')){
                            selected_posts.push(Sposts[i].dataset.postId); } }
                        Smerge_post.href="?merge_posts="+selected_posts.join(" ");
                        Smerge_post_count.innerText=selected_posts.length;
                    }
                    function ThreadMergeInput(elem){
                        if(elem.value.match(/[0-9]{14}/)) { Smerge_thread.href="?merge_threads=<?=$this->ActualPostID?> "+elem.value; }
                        else{ Smerge_thread.href="#"; }
                    }
                    function ToggleThreadMerge(force_on=false,force_off=false){
                        if((!merge_mode || force_on) && !force_off){ TogglePostSelectMode(false,true);
                            Smerge_thread_dialog.style.display="block";Smerge_thread_btn.classList.add('text_highlight');
                            Smerge_cancel.innerText='<?=$this->T("取消")?>'; merge_mode = 1;}
                        else{ merge_mode = 0;
                            Smerge_thread_dialog.style.display="none";Smerge_thread_btn.classList.remove('text_highlight');
                            Smerge_cancel.innerText='<?=$this->T("工具")?>';Smerge_select.selectedIndex='0'; }
                        Smerge_thread.href="#";
                        Smerge_thread_target.value='';
                    }
                <?php } ?>
                function ShowSideUploader(){
                    ShowRightSide(true,null);
                    put = document.querySelector("#_uploader");
                    put.style.display='block';
                    RefreshSideGallery();
                }
                dmark = document.querySelector("#mark_details");
                drename = document.querySelector("#rename_details");
                dmastodon = document.querySelector("#mastodon_details");
                drename_form = document.querySelector("#post_rename_form");
                drename_input = document.querySelector("#post_rename_name");
                var rp = document.querySelector('#post_reply_to');
                var _reply_to = rp?rp.defaultValue:"";
                function RestoreReply(){
                    if(rp){
                        rp.defaultValue = _reply_to;
                    }
                    document.querySelector('#post_reply_restore').style.display='none';
                    ht = document.querySelector('#post_hint_text');
                    if (_reply_to!=""){
                        ht.style.display='block';
                        ht.innerHTML="<?=$this->T('继续补充该话题：')?>";
                    }else{
                        ht.style.display='none';
                    }
                    document.querySelector('#post_edit_target').value="";
                    ch = document.querySelector('#post_hint_modify');
                    ch.style.display='none';
                }
                function MakeRefer(id){
                    t = document.querySelector('#post_content')
                    t.focus();
                    v = t.value;
                    t.value = v.slice(0, t.selectionStart) + "[<?=$this->T('引用文章')?>]("+id.toString()+")" + v.slice(t.selectionEnd);
                    la_auto_grow(t);
                    if(rp){
                        rp.defaultValue = "";
                        ht = document.querySelector('#post_hint_text');
                        ht.innerHTML = "<?=$this->T('引用并发送新话题：')?>"; ht.style.display='block';
                        document.querySelector('#post_reply_restore').style.display='inline';
                        rs = document.querySelector('#post_reply_restore');
                        rs.style.display='inline'; rs.innerHTML="<?=$this->T('切换为回复')?>";
                        document.getElementById('post_menu').style.display='none';
                        ch = document.querySelector('#post_hint_modify');
                        ch.style.display='none';
                    }
                }
                function CopyRefer(id){
                    copy_text(id.toString());
                    menu.querySelector('#menu_refer_copy').innerHTML="<?=$this->T('已复制')?>";
                }
                function MakeEdit(id){
                    ed = document.getElementById('menu_edit');
                    ed.innerHTML="<?=$this->T('稍等')?>";
                    ed.href="#";
                    var xhr = new XMLHttpRequest();
                    xhr.onreadystatechange = function() {
                        if (this.readyState == 4 && this.status == 200) {
                            ed.innerHTML='——';
                            ch = document.querySelector('#post_hint_modify');
                            ch.style.display='block';
                            ht = document.querySelector('#post_hint_text');
                            ht.innerHTML = "<?=$this->T('修改帖子：')?>";
                            rs = document.querySelector('#post_reply_restore');
                            rs.style.display='inline'; rs.innerHTML="<?=$this->T('取消')?>";
                            t = document.querySelector('#post_content');
                            t.value=xhr.responseText.trim();
                            t.focus();
                            la_auto_grow(t);
                            document.querySelector('#post_edit_target').value=id;
                            document.getElementById('post_menu').style.display='none';
                        }
                    };
                    xhr.open("GET", "index.php?post="+id.toString()+'&post_original=true', true);
                    xhr.send();
                }
                function MarkDelete(id){
                    p = document.querySelector('[data-post-id="'+id+'"]');
                    op = p.dataset.markDelete?"false":"true";
                    window.location.href=
                        "index.php?<?=$this->GetRedirect()?>&mark_delete="+op+'&target='+id.toString();
                }
                function SetMark(mark){
                    menu = document.getElementById('post_menu');
                    window.location.href=
                        "index.php?<?=$this->GetRedirect()?>&target="+
                        menu.parentNode.dataset.postId+"&set_mark="+mark;
                }
                function ToggleMarkDetails(){
                    dmark.style.display=(dmark.style.display=='block')?'none':'block';
                    drename.style.display=dmastodon.style.display='none'; 
                }
                function ToggleRenameDetails(){
                    drename.style.display=(drename.style.display=='block')?'none':'block';
                    dmark.style.display=dmastodon.style.display='none';
                }
                function ToggleMastodonDetails(){
                    dmastodon.style.display=(dmastodon.style.display=='block')?'none':'block';
                    dmark.style.display=drename.style.display='none';
                }
            <?php } ?>
            function ShowPostMenu(post){
                menu = document.getElementById('post_menu');
                menu.style.display='block';
                menu.parentNode.removeChild(menu);
                post.appendChild(menu);
                menu.style.display='block';
                id = post.dataset.postId
                menu.querySelector('#_time_hook').innerHTML = ''+id.substring(2,4)+'/'+id.substring(4,6)+'/'+id.substring(6,8)+
                                                              ' '+id.substring(8,10)+':'+id.substring(10,12);
                window.onClick="HidePopMenu()";
                menu.onClick=(function(event){
                  event.stopPropagation();
                });
                hs = menu.querySelector('#menu_history');
                hs.href='?post='+id+'&history=1';
                <?php if($this->LoggedIn && !isset($this->WayBack)){ ?>
                    menu.querySelector('#menu_refer').href='javascript:MakeRefer(id)';
                    ref = menu.querySelector('#menu_refer_copy')
                    ref.innerHTML='<?=$this->T("复制编号")?>';
                    ref.href='javascript:CopyRefer(id)';
                    ed = menu.querySelector('#menu_edit')
                    ed.href='javascript:MakeEdit(id)'; ed.innerHTML="<?=$this->T('修改')?>";
                    d = menu.querySelector('#menu_delete');
                    d.href='javascript:MarkDelete(\"'+id+'\")';
                    p = document.querySelector('[data-post-id="'+id+'"]');
                    d.innerHTML = p.dataset.markDelete?"<?=$this->T('恢复')?>":"<?=$this->T('删除')?>";
                    menu.querySelector('#mark_details').dataset.id=id;
                    drename_input.value=id;
                    drename_form.action="<?=$this->GetRedirect()?>"+"&rename_post="+id;
                    <?php if(isset($this->MastodonToken)){ ?>
                        dmastodon_form = document.querySelector("#post_mastodon_form");
                        dmastodon_form.action='?mastodon_post='+id;
                        function MastodonSend(id){
                            mastodon_send.innerHTML="<?=$this->T('稍等')?>";
                            var xhr = new XMLHttpRequest();
                            function wrapidxhr(_id) { return function() {
                                if (this.readyState == 4 && this.status == 200) {
                                    var response = xhr.responseText;
                                    let content="";
                                    if(res = response.match(/SUCCESS (\S+)/u)){
                                        content="<?=$this->T('成功发送到长毛象')?>";
                                    }else{content="<b><?=$this->T('出现问题')?></b><br /><span class='smaller'>"+response+"</span>";}
                                    mastodon_send.innerHTML=content; mastodon_send.href=res[1];
                                    MarkPostDoneMastodon(_id, res[1]);
                                } }
                            };
                            xhr.onreadystatechange = wrapidxhr(id);
                            xhr.open("GET", "index.php?mastodon_send_post="+id, true); xhr.send();
                        }
                        menu_mastodon = menu.querySelector("#menu_mastodon");
                        mastodon_send = menu.querySelector("#menu_mastodon_send");
                        mastodon_view = menu.querySelector("#menu_mastodon_view");
                        mastodon_url = menu.querySelector("#post_mastodon_url");
                        if(p.dataset.mastodon_url){mastodon_view.style.display='inline';mastodon_view.href=p.dataset.mastodon_url;
                            mastodon_send.innerHTML='<?=$this->T("重新发送")?>'; mastodon_url.value=p.dataset.mastodon_url; }
                        else{mastodon_view.style.display='none';mastodon_view.href=""; mastodon_send.innerHTML="<?=$this->T('发送')?>";
                            mastodon_url.value="";}
                        mastodon_send.onclick=function(){
                            this.onclick=function(){};
                            MastodonSend(id); };
                <?php } } ?>
                
                title = document.title;
                copy = document.getElementById('share_copy');
                copy.innerHTML='⎘ <?=$this->T("复制链接")?>';
                copy.addEventListener("click", function(){
                    url = window.location
                    path = location.pathname
                    copy_text(url.protocol+"//"+url.host+path+"?post="+id);
                    this.innerHTML='&#10003;&#xfe0e; <?=$this->T("复制链接")?>';
                });
            }
            function HidePopMenu(){
                var menus = document.querySelectorAll('.pop_menu');
                [].forEach.call(menus, function(m){m.style.display='none';});
            }
            var posts = document.querySelectorAll('.center .post');
            [].forEach.call(posts, function(p){
                if(s=p.querySelector('._menu_hook')) s.addEventListener("click", function() {
                    ShowPostMenu(this.parentNode.parentNode);
                });
            });
            var post_clickables = document.querySelectorAll('.center .post a');
            [].forEach.call(post_clickables, function(p){
                p.addEventListener("click", function(event){
                    event.stopPropagation();
                });
            });
            
            function FindImage(imgsrc){
                for(var i=0;i<document.images_filtered.length;i++){
                    if (document.images_filtered[i].dataset.imgsrc==imgsrc) return document.images_filtered[i]
                }
                return null
            }
            
            var dottime1;
            var dottime2;
            
            function ToDataURL(url) {
                return fetch(url).then((response) => {return response.blob();}).then(blob => {
                    clearTimeout(dottime1);clearTimeout(dottime2);
                    ps = document.querySelector('#download_processing');
                    ps.innerHTML='↓';ps.style.opacity='';
                    return URL.createObjectURL(blob);});
            }
            async function DownloadAsImage(url,name) {
                const a = document.createElement("a"); a.href = await ToDataURL(url); a.download = name;
                document.body.appendChild(a); a.click(); document.body.removeChild(a);
            }
            function basename(url){return url.split(/[\\/]/).pop();}
            
            pushed=0;
            function ShowBigImage(imgsrc,do_push){
                ShowWaitingBar();
                share = document.querySelector('#big_image_share');
                img = document.querySelector('#big_image');
                vid = document.querySelector('#big_video');
                vidsrc = document.querySelector('#big_video_src');
                down = document.querySelector('#image_download');
                img.src = ""; vidsrc.src="";
                if(imgsrc.match(/\.mp4$/)){
                    vidsrc.src = src = "images/"+imgsrc; img.style.display='none'; vid.load();vid.style.display='block';
                }else{
                    img.src = src = "images/"+imgsrc; vid.style.display='none';img.style.display='block';
                }
                down.href="images/"+imgsrc;
                var downname='<?=$this->T($this->Title);?>_<?=$this->T($this->DisplayName);?>_<?=$this->GiveSafeEMail()?>_'+
                    basename(down.href);
                img.alt = downname; down.download=downname;
                var use_href = "images/"+imgsrc;
                
                down.onclick=function(event){event.stopPropagation();event.preventDefault();
                ps = this.querySelector('#download_processing');ps.innerText='…';
                var dotwait = function(ps){ var self = this;
                    dottime1 = setTimeout(function(ps){
                        ps.style.opacity='0'; dottime2 = setTimeout(function(ps){ps.style.opacity='1'; dotwait(ps);},300,ps); },300,ps) }
                dotwait(this.querySelector('#download_processing'));
                DownloadAsImage(use_href,downname);};

                
                if(do_push){PushGalleryHistory(src)}
                
                page_url = encodeURIComponent(window.location.href);
                
                <?php if(isset($this->EMail) && $this->EMail!=""){ ?>
                    inqb = document.querySelector('#image_inquiry');
                    inqb.href="mailto:<?=$this->T($this->DisplayName);?>\<<?=$this->EMail?>\>?subject=<?=$this->T('网站图片咨询');?>&body="+
                        encodeURIComponent("<?=$this->T('你好！我对你网站上的这张图片感兴趣：')?>"+'\n\n')+
                        (page_url)+encodeURIComponent('\n\n');
                <?php } ?>
                
                this_image = FindImage(imgsrc);
                if(this_image&&this_image.dataset.prevsrc){
                    new_image = FindImage(this_image.dataset.prevsrc);
                    new_prev = new_image?new_image.dataset.prevsrc:null;
                    im = document.querySelector('#prev_image');
                    if(new_prev) {im.href='javascript:ShowBigImage("'+this_image.dataset.prevsrc+'",'+do_push+')';im.style.opacity='';}
                    else {im.style.opacity='0';im.removeAttribute("href");}
                }
                if(this_image&&this_image.dataset.nextsrc){
                    new_image = FindImage(this_image.dataset.nextsrc);
                    new_next = new_image?new_image.dataset.nextsrc:null;
                    im = document.querySelector('#next_image');
                    if(new_next) {im.href='javascript:ShowBigImage("'+this_image.dataset.nextsrc+'",'+do_push+')';im.style.opacity='';}
                    else {im.style.opacity='0';im.removeAttribute("href");}
                }
                purchase = document.querySelector('#image_purchase');
                purchase_btn = document.querySelector('#image_purchase_button');
                <?php if($this->LoggedIn){ ?>
                    product_link = document.querySelector('#image_ops_product_link');
                    product_form = document.querySelector('#image_ops_form');
                    edit_form = document.querySelector('#image_edit_form');
                    edit_new_name = document.querySelector('#image_edit_new_name');
                    edit_parent = document.querySelector('#image_parent');
                    product_form.action = window.location.href;
                    edit_form.action = window.location.href;
                    edit_new_name.value = imgsrc.split('.')[0];
                    if(this_image&&this_image.dataset.product){ product_link.value = this_image.dataset.product; }else{ product_link.value = ""; }
                    if(this_image&&this_image.dataset.parent){ edit_parent.value = this_image.dataset.parent; }else{ edit_parent.value = ""; }
                <?php } ?>
                if(this_image&&this_image.dataset.product){
                    purchase.style.display='';
                    href = this_image.dataset.product;
                    if(this_image.dataset.product.match('^[0-9]{14}$')){
                        href = '?post='+href;
                    }
                    purchase_btn.href = href;
                }else{
                    purchase.style.display='none';
                    purchase_btn.removeAttribute("href");
                }
                
                DelayHideImgBtn(null);
                
                title = encodeURIComponent(document.title);
                copy = document.getElementById('big_share_copy');
                copy.innerHTML='⎘ <?=$this->T("复制链接")?>';
                copy.addEventListener("click", function(){
                    copy_text(window.location.href);
                    this.innerHTML='&#10003;&#xfe0e; <?=$this->T("复制链接")?>';
                });
                
                o = document.querySelector('#big_image_overlay');
                info = document.querySelector('#big_image_info');info.innerHTML="<?=$this->T('正在查询……')?>";
                here = document.querySelector('#here_buttons');here.innerHTML='';
                o.style.display="block";
                ShowBackdrop(0.8);
                var xhr = new XMLHttpRequest();
                xhr.onreadystatechange = function() {
                    if (this.readyState == 4 && this.status == 200) {
                        info = document.querySelector('#big_image_info');
                        var response = xhr.responseText;
                        let content=""
                        if(res = response.match(/<ref>(.*)<\/ref>/u)){
                            content="<span class='small'><?=$this->T('该图片出现在')?> "+res[1]+" <?=$this->T('个帖子中')?></span>"+content;
                        }else{content="<span class='smaller gray'><?=$this->T('该图片未被引用')?></span>"+content;}
                        if(res = response.match(/<insert>([\s\S]*)<\/insert>/u)){
                            content+="<div class='clean_a'>"+res[1]+"</div>"; }
                        info.innerHTML=content;
                        if(res = response.match(/<here>([\s\S]*)<\/here>/u)){
                            here.innerHTML=res[1]; window.here_b=document.getElementsByClassName('here_btn');
                            if(window.here_b)for(b=0;b<window.here_b.length;b++) {
                                window.here_b[b].addEventListener('mousemove',DontHideImgBtn);
                                window.here_b[b].addEventListener('mouseover',DontHideImgBtn);}
                        }
                    }
                };
                xhr.open("GET", "index.php?image_info="+imgsrc+"", true);
                xhr.send();
            }
            function HideBigImage(do_push){
                o = document.querySelector('#big_image_overlay');
                img = document.querySelector('#big_image');
                vid = document.querySelector('#big_video');
                vidsrc = document.querySelector('#big_video_src');
                img.src = ""; vidsrc.src="";
                if(o.style.display!='none'){
                    o.style.display="none";
                    HideBackdrop();
                    if(do_push){PushGalleryHistory("");}
                    HideWaitingBar();
                }
            }
            var lbtn=document.querySelector('#prev_image'),rbtn=document.querySelector('#next_image');
            var inq=document.querySelector('#inquiry_buttons');
            var overlay=document.querySelector('#big_image_overlay');
            var hide_timeout;
            function DontHideImgBtn(e){ clearTimeout(hide_timeout); e.stopPropagation(); }
            function DelayHideImgBtn(e){
                lbtn.classList.remove('img_btn_hidden'); rbtn.classList.remove('img_btn_hidden');
                inq.classList.remove('img_btn_hidden');
                if(window.here_b)for(b=0;b<window.here_b.length;b++){window.here_b[b].classList.remove('img_btn_hidden');}
                clearTimeout(hide_timeout);
                hide_timeout = setTimeout(function(e1,e2,e3,e4){e1.classList.add('img_btn_hidden');
                    e2.classList.add('img_btn_hidden');e3.classList.add('img_btn_hidden');
                    if(window.here_b)for(b=0;b<window.here_b.length;b++){
                        window.here_b[b].classList.add('img_btn_hidden');}}, 1000, lbtn, rbtn, inq);
            }
            lbtn.addEventListener('mousemove',DontHideImgBtn);lbtn.addEventListener('mouseover',DontHideImgBtn);
            rbtn.addEventListener('mousemove',DontHideImgBtn);rbtn.addEventListener('mouseover',DontHideImgBtn);
            inq.addEventListener('mousemove',DontHideImgBtn);inq.addEventListener('mouseover',DontHideImgBtn);
            overlay.addEventListener('mousemove',DelayHideImgBtn);
            var images = document.querySelectorAll('img, video');
            var images_filtered=new Array(); var imgadded = new Array();
            var images_remaining=new Array();
            [].forEach.call(images, function(img){
                if(img.classList.contains("no_pop") || (!(imgsrc = img.dataset.imgsrc))) return;
                if(imgadded.indexOf(imgsrc)>=0) {images_remaining.push(img); return;}
                images_filtered.push(img);imgadded.push(imgsrc);
            });
            for(var i=0; i<images_filtered.length; i++){
                previmg = nextimg = null; img = images_filtered[i];
                if(i>0) previmg=images_filtered[i-1];
                if(i<images_filtered.length-1) nextimg=images_filtered[i+1];
                prevsrc=previmg?previmg.dataset.imgsrc:null; nextsrc=nextimg?nextimg.dataset.imgsrc:null; 
                img.dataset.prevsrc = prevsrc; img.dataset.nextsrc = nextsrc; 
                function wrap(imgsrc){return function(){ShowBigImage(imgsrc, 1);}}
                img.addEventListener("click", wrap(img.dataset.imgsrc));
            }
            for(var i=0; i<images_remaining.length; i++){
                img = images_remaining[i];
                function wrap(imgsrc){return function(){ShowBigImage(imgsrc, 1);}}
                img.addEventListener("click", wrap(img.dataset.imgsrc));
            }
            document.images_filtered = images_filtered;
            function PopGalleryHistory(){
                if(pushed){
                    pushed = 0;
                    try{
                        history.back();
                    }catch{
                        console.log("can't do it.");
                    }
                }
            }
            function PushGalleryHistory(src){
                abs_img = window.location.protocol+"//"+window.location.host+'/'+src
                title = "照片"
                extra = "?";
                sp = new URLSearchParams(window.location.search)
                if(sp.has('post')){extra+="post="+sp.get('post')}
                if(sp.has('gallery')){extra+="&gallery="+sp.get('gallery')}
                try{
                    window.history.pushState('&pic='+src, 'Title', extra+'&pic='+src);
                }catch{
                    console.log("can't do it.");
                }
                pushed = 1;
            }
            document.addEventListener('keydown', function(e){
                large = document.getElementById('big_image_overlay')
                if (large.style.display!='block') return;
                if(e.key=='Escape'||e.key=='Esc'||e.keyCode==27){
                    HideBigImage(1);
                }
            }, true);
            window.addEventListener('popstate', (event) => {
                if(event.state){
                    let sp = new URLSearchParams(event.state)
                    if(sp.has('pic')){
                        src = sp.get('pic')
                        if(onlyimg = src.match(/[0-9]{14,}.(jpg|png|jpeg|gif|mp4)/u)) ShowBigImage(onlyimg[0], 0);
                        else{HideBigImage(0);}
                    }
                }else{HideBigImage(0);}
            });
            
            let searchParams = new URLSearchParams(window.location.search)
            if(searchParams.has('pic')){
                src = searchParams.get('pic')
                if(onlyimg = src.match(/[0-9]{14,}.(jpg|png|jpeg|gif|mp4)/u)){
                    ShowBigImage(onlyimg[0], 1);
                }
            }
            function _dropHandler(event){ if (typeof dropHandler === "function") dropHandler(event); }
            function _dragOverHandler(event){ if (typeof dragOverHandler === "function") dragOverHandler(event); }
        </script>
        </body>
    <?php
    }
    
    function DoIdentifyExperimental(){
        if(isset($this->ExpHost) && $this->ExpHost && preg_match('/'.preg_quote($this->ExpHost).'/u', $_SERVER['HTTP_HOST'])){
            $this->InExperimentalMode=True; $this->InHereMode=True;
        }
        if(isset($this->HereHost) && $this->HereHost && preg_match('/'.preg_quote($this->HereHost).'/u', $_SERVER['HTTP_HOST'])){
            $this->InHereMode=True;
        }
    }
    function DoExperimentalTopLink($p){
        if($this->InHereMode && $p){
            if(isset($p['tid']) && $p['tid']['first']!=$p){
                header('Location: ?post='.$p['tid']['first']['id']); exit();
            }
        }
    }
    function MakeExperimentalConfirm(){
        if(isset($_COOKIE['la_experimental']) && $_COOKIE['la_experimental'] == 'confirmed'){
            return false;
        }
        $caution_html = 
        $confirm = "<a class='text_highlight clean_a bold' href='index.php?confirm_enter=1".
                      (isset($this->CurrentPostID)?("&post=".$this->CurrentPostID):"").
                        "'>&nbsp;&nbsp;".$this->T('继续')."&nbsp;&nbsp;</a>";
        ?>
        <div class='center_exp'><?php
            if(isset($this->ExpCaution) && ($p=$this->GetPost($this->ExpCaution,true)))$this->MakeSinglePostExp($p);
            else echo "<li class='post post_dummy'>".$this->TranslatePostParts("<h1>注意</h1><p>您将进入实验站。</p>")."</li>";
        ?></div>
        <div class='center_exp'><li class='post post_dummy'><p><?=$confirm?></p></li></div>
        <?php return true;
    }
}

$la = new LA;

$la->DoSiteRedirect();

$la->DoLogin();

$la->SwitchLanguageAndFont();

$err = $la->ProcessRequest($message, $redirect);
$la->WriteAsNecessary();

$la->DoIdentifyExperimental();

$la->SwitchWayBackMode();

if($err){
    echo $message;
    exit();
}

if(isset($redirect)){
    header('Location: '.$redirect);
    exit();
}

$la->DetectPageType();

$la->ReadImages(false);
$la->ReadPosts();

if(isset($la->WayBack)){
    $la->ReadArchive();
}

$im = NULL;
if(preg_match('/images\/(.*)/u',$la->HereID,$imname) && ($im = &$la->FindImage($imname[1]))){
    if(!$la->CanShowImage($im)) { $im=&$la->NULL_IMAGE; }
}

$p = &$la->GetPost($la->CurrentPostID);
if(!isset($p)){
    $p = &$la->GetMergedPost($la->CurrentPostID);
}

if(isset($p) && !$la->CanShowPost($p)) $p=NULL;
else{ $la->DoExperimentalTopLink($p); }

if(isset($p)){ $la->ActualPostID = $p['id']; }

if($la->PageType=='here' ||$la->PageType=='experimental'){
    if($im){
        $la->SetHereMainImage($im);
        $la->RecordVisitedHere($im['name'],$im?$la->ImageTitle($im):NULL);
    }else if($p){
        $la->RecordVisitedHere($p['id'],NULL);
    }else {
        $im = &$la->GiveImageInHere(false);
        if($im){
            $la->SetHereMainImage($im);
            $la->RecordVisitedHere($im['name'],$la->ImageTitle($im));
        }
    }
}

$la->MakeHeader($p);
$la->MakeMainBegin();



if($la->PageType=='here' ||$la->PageType=='experimental'){
    if(($la->PageType=='experimental' && (!$la->MakeExperimentalConfirm())) || $la->PageType=='here'){
        if($im){
            $la->MakeHereSection($im);
        }else if($p){
            $la->MakePostSectionExp($p);
            $la->MakeLinkedPostsExp($p);
        }else {
            if($im){
                $la->MakeHereSection($im);
            }else{
                echo "<h2>".$la->T('未找到这个帖子')."</h2><p>".$_SERVER['REQUEST_URI'].
                    "</p><p><a href='index.php'>".$la->T('返回首页')."</a></p><br />";
            }
        }
    }
    $la->MakeMainEnd();
    $la->MakeExpFooter();
}else{
    if($la->PageType=='extras'){
        $la->MakeExtraOperations();
    }else if($la->PageType=='settings'){
        $la->MakeSettings();
    }else if($la->PageType=='gallery'){
        $la->MakeGalleryLeft();
        $la->MakeGallerySection();
    }else if($la->PageType=='post'){
        if($p){
            $made_interesting = false;
            if($la->IsInterestingPost($p)){
                $la->MakeInterestingSection($p['tid']);
            }
            else{
                $la->MakeLinkedPosts($p);
                $la->MakePostSection($p);
                $la->MakeTOC();
            }
        }else{
            echo "<h2>".$la->T('未找到这个帖子')."</h2><p>".$_SERVER['REQUEST_URI'].
                "</p><p><a href='index.php'>".$la->T('返回首页')."</a></p><br />";
        }
    }else if($la->PageType=='history'){
        $la->ReadArchive();
        $la->MakePostHistory($p, $_GET['version']??NULL);
    }else if($la->PageType=='search'){
        $la->MakeHotPosts(true);
        $la->MakeRecentPosts($_GET['search']);
    }else if($la->PageType=='category'){
        $la->MakeHotPosts(true);
        $la->MakeRecentPosts(NULL,$_GET['category']);
    }else if($la->PageType=='comments'){
        $la->MakeCommentPosts();
    }else{
        $la->MakeHotPosts();
        $la->MakeRecentPosts();
    }
    $la->MakeMainEnd();
    $la->MakeFooter($p);
}

?>

