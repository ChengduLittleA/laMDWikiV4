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
    protected $SpecialNavigation;
    protected $SpecialFooter;
    protected $SpecialFooter2;
    protected $SpecialPinned;
    protected $Redirect;
    protected $Translations;
    
    protected $CurrentOffset;
    protected $PostsPerPage;
    protected $HotPostCount;
    
    protected $LoggedIn;
    protected $LanguageAppendix;
    
    protected $Posts;
    protected $Threads; // [ keys: first last displayed count]
    protected $Images;
    protected $Galleries;
    protected $Anchors;
    
    protected $Markers;
    
    protected $ExtraScripts;
    
    protected $NULL_POST;
    protected $NULL_IMAGE;
    protected $NULL_Gallery;
    
    public $PageType;
    public $CurrentPostID;
    
    function T($str){
        if(!$this->LanguageAppendix) return $zh;
        foreach($this->Translations as $entry){
            if($entry['zh']==$str)
                return $entry[$this->LanguageAppendix];
        }
        return $str;
    }
    function SwitchLanguage(){        
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
                    header('Location:https://'.$r['domain'].'/index.php?post='.$r['target']); exit;
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
    
    function WriteConfig(){
        if(!isset($this->Title)) $this->Title = $this->T('那么的维基');
        if(!isset($this->ShortTitle)) $this->ShortTitle = $this->T('基');
        if(!isset($this->Admin)) $this->Admin = 'admin';
        if(!isset($this->DisplayName)) $this->DisplayName = $this->T('管理员');
        if(!isset($this->Password)) $this->Password = password_hash('Admin', PASSWORD_DEFAULT).PHP_EOL;
        $conf = fopen('la_config.md','w');
        fwrite($conf,'- Title = '.$this->Title.PHP_EOL);
        fwrite($conf,'- ShortTitle = '.$this->ShortTitle.PHP_EOL);
        fwrite($conf,'- Admin = '.$this->Admin.PHP_EOL);
        fwrite($conf,'- DisplayName = '.$this->DisplayName.PHP_EOL);
        fwrite($conf,'- Password = '.$this->Password.PHP_EOL);
        fwrite($conf,'- SpecialNavigation = '.$this->SpecialNavigation.PHP_EOL);
        fwrite($conf,'- SpecialFooter = '.$this->SpecialFooter.PHP_EOL);
        fwrite($conf,'- SpecialFooter2 = '.$this->SpecialFooter2.PHP_EOL);
        fwrite($conf,'- SpecialPinned = '.$this->SpecialPinned.PHP_EOL);
        fflush($conf);fclose($conf);
        $conf = fopen('la_redirect.md','w');
        fwrite($conf,$this->DisplayRedirectConfig());fflush($conf);fclose($conf);
        $this->WriteHTACCESS();
    }
    
    function Install(){
        if(!file_exists('la_config.md')){
            $this->WriteConfig();
        }
        if(!is_dir('posts')) mkdir('posts');
        if(!is_dir('images')) mkdir('images');
        if(!is_dir('images/thumb')) mkdir('images/thumb');
        if(!is_dir('styles')) mkdir('styles');
        
        $this->WriteStyles();
    }
    
    function ReadConfig(){
        if(!file_exists('la_config.md')){
            $this->Install();
        }
        $c = file_get_contents('la_config.md');
        if(preg_match('/-\s*Title\s*=\s*(\S+)\s*$/um', $c, $m)) $this->Title = $m[1]; else $this->Title=$this->T("那么的维基");
        if(preg_match('/-\s*ShortTitle\s*=\s*(\S+)\s*$/um', $c, $m)) $this->ShortTitle = $m[1]; else $this->Title=$this->T("基");
        if(preg_match('/-\s*Admin\s*=\s*(\S+)\s*$/um', $c, $m)) $this->Admin = $m[1];
        if(preg_match('/-\s*Password\s*=\s*(\S+)\s*$/um', $c, $m)) $this->Password = $m[1];
        if(preg_match('/-\s*DisplayName\s*=\s*(\S+)\s*$/um', $c, $m)) $this->DisplayName = $m[1];
        if(preg_match('/-\s*SpecialNavigation\s*=\s*(\S+)\s*$/um', $c, $m)) $this->SpecialNavigation = $m[1];
        if(preg_match('/-\s*SpecialFooter\s*=\s*(\S+)\s*$/um', $c, $m)) $this->SpecialFooter = $m[1];
        if(preg_match('/-\s*SpecialFooter2\s*=\s*(\S+)\s*$/um', $c, $m)) $this->SpecialFooter2 = $m[1];
        if(preg_match('/-\s*SpecialPinned\s*=\s*(\S+)\s*$/um', $c, $m)) $this->SpecialPinned = $m[1];
        if(file_exists('la_redirect.md')){
            $c = file_get_contents('la_redirect.md');
            $this->BuildRedirectConfig($c);
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
            $c = file_get_contents('custom_translations.md');
            if(preg_match_all('/-\s+(\S.*)\s*\|\s*(\S.*)$/um',$c, $ma, PREG_SET_ORDER)) foreach($ma as $m){
                $entry = []; $entry['zh'] = trim($m[1]); $entry['en'] = trim($m[2]);
                $this->Translations[] = $entry;
            }
        }
    }
    
    function __construct() {
        $this->ReadConfig();
        $this->PDE = new ParsedownExtra();
        $this->PDE->SetInterlinkPath('/');
        $this->Posts = [];
        $this->Threads = [];
        
        $this->Markers=['●', '○', '✓', '×', '!'];
        
        $this->PostsPerPage = 40;
        $this->HotPostCount = 15;
    }
    
    function DoLogout(){
        $this->LoggedIn = false;
        unset($_SESSION['user_id']); unset($_SESSION['la_theme']);
    }
    
    function DoLogin(){
        session_start();
        $redirect=false;
        if(isset($_GET['logout'])){ $this->DoLogout(); }
        else if(!isset($_SESSION['user_id'])){
            if(isset($_POST['login_button'])){
                $id = trim($_POST['login_id']);
                $pwd = trim($_POST['login_password']);
                if(strtolower($this->Admin)==strtolower($id)&&password_verify($pwd, $this->Password)){
                    $_SESSION['user_id']=$id;
                    
                }
                $redirect = true;
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
    
    function WriteStyles(){
        $this->style="
html{font-size:18px;font-family:'Noto Serif CJK SC','Times New Roman','SimSun',Georgia,serif;}
body{background-color:%white%;color:%black%;}
*{box-sizing:border-box;padding:0;margin:0;}
.page,.page_gallery{padding:1em;padding-top:0;}
.hidden_on_desktop, .hidden_on_wide{display:none;}
::file-selector-button{background:none;border:none;}
a,button,::file-selector-button{text-decoration:underline;color:%black%;}
a:hover,button:hover,::file-selector-button:hover{text-decoration:none;color:%gray%;}
header{position:sticky;top:0;background-color:%white%;z-index:10;padding-top:1em;}
header a,.left a,.footer a,.clean_a,.clean_a a{text-decoration:none;}
header a:hover,.button:hover{color:%gray% !important;}
.invert_a,.invert_a a{color:%gray%;text-decoration:none;}
.invert_a:hover,.invert_a a:hover{color:%black% !important;}
.gray{color:%gray%;}
hr{border:1px solid %gray%;}
p{margin:0;margin-bottom:0.5em;}
p:last-child{margin-bottom:0;}
header ul{display:inline-block;}
header li{display:inline-block;}
header li::before{content:' - '}
header h1,header h2,header h3,header h4,header h5,header p{display:inline;font-size:1rem;}
.main{position:relative;word-spacing:-1em;}
.main div{word-spacing:initial;}
pre{overflow:auto;}
ul{display:block;}
li{display:block;}
table{width:100%;border-collapse:collapse;border-bottom:2px solid %black%;border-top:2px solid %black%;}
table input{border:none!important;}
td{padding-left:0.1em;padding-right:0.1em;}
td:first-child{padding-left:0;}
td:last-child{padding-right:0;}
tbody tr:hover{box-shadow:inset 0 -2px 0 0px %black%;}
thead{border-bottom:1px solid %black%;} 
.left{display:inline-block;vertical-align:top;width:25%;height:calc(100vh - 5.2em);
position:sticky;top:2.5em;overflow:auto;padding-right:0.2em;}
.center{display:inline-block;vertical-align:top;width:50%;padding-left:0.3em;overflow:auto;}
.center .post:hover{background-color:%graybkg%;}
.right{display:inline-block;vertical-align:top;width:25%;position:sticky;top:2.5em;padding-left:0.5em;height:calc(100vh - 2.6em);overflow:auto;}
textarea,input[type=input],input[type=password]{width:100%;display:block;font-family:inherit;max-height:60vh;}
select,textarea,input[type=input],input[type=password]{background:none;border:none;border-bottom:1px solid %black%;color:%black%;}
.button{background:none;border:none;font-family:inherit;color:%black%;font-size:inherit;font-weight:bold;}
.focused_post{font-size:1.5em;}
.post{position:relative;scroll-margin:2.5em;border-radius:0.3em;padding:0.3rem;padding-left:0rem;}
.post{margin-top:0.2em;margin-bottom:0.2em;}
.post_width li,.post_width_big li,.footer_additional li,.footer_additional li{display:list-item;margin-left:1em;list-style:disc;}
.post_width li li,.post_width_big li li,.footer_additional li li,.footer_additional li li{list-style:circle;}
.focused_post{margin-top:0.1em;margin-bottom:0.1em;padding-left:0.3rem;}
.post_width{position:relative;left:1.4rem;width:calc(100% - 1.6rem);padding-left:0.2rem;}
.post_width_big{position:relative;left:0;width:100%;}
.post_menu_button{position:absolute;display:none;right:0;width:1.5rem;
text-align:center;border-radius:0.3em;user-select:none;cursor:pointer;}
.pointer{cursor:pointer;}
.post:hover .post_menu_button{display:block;}
.pop_menu{position:absolute;top:0;z-index:95;background-color:%lighterbkg%;
padding:0.3em;right:0;text-align:right;border-radius:0.3em;font-size:1rem;
box-shadow:0px 0px 10px rgb(0, 0, 0);}
.pop_menu hr{border:2px solid rgba(0,0,0,0.1);}
.toc{left:60%;width:40%;top:0;position:absolute;}
.post_access{width:1.4em;top:0;position:absolute;height:100%;text-align:center;
font-weight:bold;border-right:2px solid transparent;padding-top:0.3rem;}
.post_access:hover{background-color:rgba(0,0,0,0.05);border-top-left-radius:0.3em;border-bottom-left-radius:0.3em;}
.post_access:hover{border-right:2px solid %black%;}
.post_box{border:1px solid %gray%;border-radius:0.3em;padding:0.3em;}
.post_box:hover,.post_menu_button:hover{background-color:%lightopbkg%}
#big_image_info .post_box:hover{background-color:%graybkg%;}
.post_preview{font-size:0.9rem;overflow:hidden;}
.post .post_ref{font-size:0.9rem;margin:0.3em;}
.post_ref .post_ref_inner{margin-left:1.2em;}
.post_ref .post_ref_inner::before{content:'→';color:%gray%;margin-left:-1em;}
.post_ref_main{max-height:6.5rem;display:inline-block;vertical-align:top;overflow:hidden;}
.post_preview .post_ref_main{max-height:6rem;overflow:hidden;}
.post_ref_images{overflow:hidden;}
.post_ref_images img{max-height:4em !important;max-width:4em !important;}
.post_reply{border-left:2px solid %gray%;padding-left:0.3rem;}
.post_reply:hover{border-left:2px solid %black%;padding-left:0.3rem;}
.page_selector{padding-top:2rem;text-align:center;}
.focused_post .post_ref{font-size:1rem;}
.smaller{font-size:0.9em;}
.block{display:block;}
.opt_compact{margin-left:1.9em;}
.post_box_top{padding-bottom:0.3em;padding-top:0.3em;}
.post_box_fixed_bottom{position:sticky;bottom:0em;background-color:%white%;z-index:5;}
.focused_post .opt_compact{font-size:0.6em !important;padding-left:0;}
.spacer{height:0.5em;}
.pop_right,.pop_right_big{position:fixed;top:0;right:0;bottom:0;width:30%;z-index:100;background-color:%graybkg%;display:none;
transition-timing-function:ease-out;padding:1rem;overflow:auto;}
@keyframes pop_slide_in{0%{right:-30%;}100%{right:0%;}}
@keyframes pop_slide_out{0%{right:0%;}100%{right:-30%;}}
@keyframes pop_slide_in_big{0%{right:-30%;}100%{right:0%;}}
@keyframes pop_slide_out_big{0%{right:0%;}100%{right:-30%;}}
.backdrop{position:fixed;top:0;right:0;bottom:0;left:0;background-color:rgba(0,0,0,0.2);transition-timing-function:ease-out;z-index:90;}
@keyframes backdrop_fade_in{0%{opacity:0%;}100%{opacity:100%;}}
@keyframes backdrop_fade_out{0%{opacity:100%;}100%{opacity:0%;}}
.toc_entry_1{font-size:1.1em;}
.toc_entry_2{font-size:1.0em;padding-left:0.5rem;}
.toc_entry_3{font-size:0.9em;padding-left:1rem;}
.toc_entry_4{font-size:0.85em;padding-left:1.5rem;}
.toc_entry_5{font-size:0.8em;padding-left:2rem;}
h1,h2,h3,h4,h5{scroll-margin:1.5em;}
{display:inline}
.left ul h1,.left ul h2,.left ul h3,.left ul h4,.left ul h5,.left ul p,
.post_ref_inner p,.post_ref_inner h1,.post_ref_inner h2,.post_ref_inner h3,.post_ref_inner h4,.post_ref_inner h5
{font-size:1em;display:inline-block;}
.deleted_post{color:%gray%;text-decoration:line-through;}
#file_list{margin-top:0.5em;}
.file_thumb img{max-height:100%;max-width:100%;object-fit:cover;min-width:100%;min-height:100%;}
#file_list li{margin-bottom:0.3em;}
.ref_thumb{white-space:nowrap;overflow:hidden;}
.ref_thumb .file_thumb{width:3em;height:3em;}
.side_thumb li{margin:0.2em;display:inline-block;}
.file_thumb{width:4em;height:4em;display:inline-block;box-shadow:0px 0px 10px rgb(0, 0, 0);
line-height:0;vertical-align:middle;overflow:hidden;}
.p_row{display:flex;flex-wrap:wrap;}
.p_thumb{display:flex;flex-grow:1;height:8rem;margin-right:0.25rem;margin-bottom:0.25rem;
box-shadow:0px 0px 10px rgb(0, 0, 0);overflow:hidden;position:relative;}
.p_thumb img{object-fit:cover;max-height:100%;min-width:100%;}
.ref_count,.p_thumb .post_menu_button{text-shadow: 0px 0px 10px rgb(0, 0, 0);}
.p_thumb:hover .post_menu_button{display:block;}
.p_thumb_selected{color:%black% !important;}
.p_thumb_selected{display:block;}
.post .p_thumb img{max-height:8rem;}
.big_image_box{position:fixed;top:0;bottom:0;left:0;width:75%;z-index:95;text-align:center;}
.big_image_box img{position:absolute;margin:auto;top:0;left:0;right:0;bottom:0;box-shadow: 0px 0px 30px black;cursor:unset;}
.big_side_box{position:fixed;top:0;bottom:0;right:0;width:25%;overflow:auto;z-index:98;color:%black%;padding:1rem;
background:linear-gradient(to right, rgba(0,0,0,0), rgb(1, 1, 1));transition:background-size .2s linear;background-size: 300% 100%;}
.big_side_box:hover{background-size: 100% 100%;}
.big_side_box a,.big_side_box hr,#dropping_background{color:%black%;}
.big_side_box a:hover{color:%gray%;}
#dropping_background{background-color:rgba(0,0,0,0.5);position:fixed;top:0;right:0;bottom:0;left:0;z-index:100;text-align:center;
box-shadow:0px 0px 500px rgba(0,0,0,0.7) inset;}
img{cursor:pointer;max-height:100%;max-width:100%;}
.post img{box-shadow:0px 0px 10px rgb(0, 0, 0);max-height:min(70vh, 20rem);;max-width:min(100%, 20rem);}
no_pop{cursor:unset;}
p{min-height:0.8em;}
.bold{font-weight:bold;}
.footer_additional{display:inline-block;width:50%;vertical-align:text-top;white-space:normal;}
.small_footer{position:sticky;bottom:0em;background-color:%white%;padding-bottom:1em;margin-top:5rem;}
.top_post_hint{margin-left:1.5em;font-weight:bold;}
.white{color:%white%;}

@media screen and (max-width:1000px){
.left{width:35%;}
.center{width:65%;}
.right{display:none;}
.post_width{left:1.5em;width:calc(100% - 1.7rem);padding-left:0.2em;}
.post_width_big{left:0;width:100%;}
.hidden_on_wide{display:unset;}
.hidden_on_narrow{display:none;}
.pop_right{width:30%;}
.pop_right_big{width:40%;}
@keyframes pop_slide_in{0%{right:-30%;}100%{right:0%;}}
@keyframes pop_slide_out{0%{right:0%;}100%{right:-30%;}}
@keyframes pop_slide_in_big{0%{right:-40%;}100%{right:0%;}}
@keyframes pop_slide_out_big{0%{right:0%;}100%{right:-40%;}}
.big_side_box{width:35%;}
.big_image_box{width:65%;}
}

@media screen and (max-width:666px){
.hidden_on_mobile{display:none !important;}
.block_on_mobile{display:block !important;}
.hidden_on_desktop{display:unset;}
header ul{display:block;}
header li{display:block;}
header li::before{content:''}
.left{position:relative;width:100%;position:relative;top:unset;height:unset;min-height:80vh;padding-right:0;display:block;}
.center{position:relative;left:0;top:0;width:100%;padding-left:0;display:block;}
.pop_right,.pop_right_big{top:unset;right:0;bottom:0;left:0;width:100%;}
.pop_right{height:30%;}
.pop_right_big{height:70%;}
@keyframes pop_slide_in{0%{bottom:-30%;}100%{bottom:0%;}}
@keyframes pop_slide_out{0%{bottom:0%;}100%{bottom:-30%;}}
@keyframes pop_slide_in_big{0%{bottom:-70%;}100%{bottom:0%;}}
@keyframes pop_slide_out_big{0%{bottom:0%;}100%{bottom:-70%;}}
.big_image_box{position:fixed;top:0;bottom:5rem;left:0;right:0;width:100%;}
.side_box_mobile_inner{background:linear-gradient(to bottom, rgba(0,0,0,0), rgba(1,1,1,0.9) 30%);
transition:none;background-size:100% 100%;padding:1rem;}
.side_box_mobile_inner:hover{background-size:100% 100%;}
.big_side_box{position:fixed;top:0;bottom:0;right:0;left:0;width:100%;
height:unset;padding:0;padding-top:calc(100vh - 6rem);background:none;}
.p_thumb{height:6rem;}
.post .p_thumb img{max-height:6rem;}
.page,.page_gallery{padding:0.3em;padding-top:0;}
header{padding-top:0.3em;}
.small_footer{padding-bottom:0.3em;}
.footer_additional{display:block;width:100%;}
}
";
        $this->style=preg_replace('/%white%/','#231a0d',$this->style);
        $this->style=preg_replace('/%black%/','#f8ca9b',$this->style);
        $this->style=preg_replace('/%gray%/','#ac7843',$this->style);
        $this->style=preg_replace('/%graybkg%/','#39270e',$this->style);
        $this->style=preg_replace('/%lightopbkg%/','#daae8010',$this->style);
        $this->style=preg_replace('/%lighterbkg%/','#675340',$this->style);
        $f = fopen('styles/main.css','w');
        fwrite($f,$this->style);
        fclose($f);
    }
    
    function &FindImage($name){
        if(isset($this->Images[0]))foreach($this->Images as &$im){
            if($im['name']==$name) return $im;
        }
        return $this->NULL_IMAGE;
    }
    
    function ReadImages($clear_non_exist = false){
        $path = 'images/list.md';
        if(!file_exists($path)){ $f = fopen($path,'w'); fflush($f); fclose($f); }
        $c = file_get_contents($path);
        if(preg_match_all('/GALLERY\s+(\S+)(.*)$/mu', $c, $ma, PREG_SET_ORDER)) foreach($ma as $m){
            $g['name']=$m[1];//$g['count']=0;
            //if(preg_match('COUNT\s+([0-9]+)\s*;/u', $m[2], $arg)){ $g['count']=$arg[1]; }
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
            $this->Images[] = $item;
        }
        
        $files = array_merge([],glob('images/*.jpg'));
        $files = array_merge($files,glob('images/*.jpeg'));
        $files = array_merge($files,glob('images/*.png'));
        $files = array_merge($files,glob('images/*.gif'));
        if(isset($files[0]))foreach($files as $file) {
            if(preg_match('/[0-9]{14,}\.(jpg|jpeg|gif|png)/u', $file, $m)) {
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
            return ($a['name'] > $b['name']) ? 1 : -1;
        }
        function cmpaf($a, $b){
            if ($a['name'] == $b['name']) return 0;
            return ($a['name'] > $b['name']) ? -1 : 1;
        }
        if(isset($this->Galleries[0]))usort($this->Galleries,"cmpf");
        if(isset($this->Images[0]))usort($this->Images,"cmpaf");
    }
    
    function WriteImages(){
        $path = 'images/list.md';
        $f = fopen($path,'w');
        if(isset($this->Galleries[0]))foreach($this->Galleries as &$g){
            if(isset($g['deleted'])) continue;
            fwrite($f,'GALLERY '.$g['name'].' ;');
            fwrite($f, PHP_EOL);
        }
        if(isset($this->Images[0]))foreach($this->Images as &$im){
            if(isset($im['deleted'])) continue;
            fwrite($f, "- ".$im['name'].'; ');
            if(isset($im['refs']) && isset($im['refs'][0])){
                fwrite($f, 'REFS '.implode(" ",$im['refs'])."; ");
            }
            if(isset($im['galleries']) && isset($im['galleries'][0])){
                fwrite($f, 'GAL '.implode(" ",$im['galleries'])."; ");
            }
            fwrite($f, PHP_EOL);
        }
        fflush($f);
        fclose($f);
    }
    
    function EditImage($name, $link_gallery, $do_remove = false){
        if(!($im = &$this->FindImage($name))) return;
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
    
    function compressImage($source, $destination, $thumb_destination, $quality, $sizelim) {
        $info = getimagesize($source);
        if ($info['mime'] == 'image/jpeg')
            $image = imagecreatefromjpeg($source);
        else if ($info['mime'] == 'image/png')
            $image = imagecreatefrompng($source);
        else return;
        list($width, $height) = getimagesize($source);
        $newwidth = $width;
        $newheight = $height;
        $anychanged = false;
        if ($width > $sizelim) {
            $newwidth = $sizelim; $newheight = ($height / $width) * $newwidth; $anychanged = true;$width=$newwidth;$height=$newheight;
        }
        if ($height > $sizelim) {
            $newheight = $sizelim; $newwidth = ($width / $height) * $newheight; $anychanged = true;
        }
        $newimage=$image;
        if($anychanged){ $newimage = imagescale($image, $newwidth,$newheight, IMG_BICUBIC); }
        imagejpeg($newimage, $destination, $quality);
        imagedestroy($newimage);
        $sizelim=400; $anychanged=false;
        if ($width > $sizelim) {
            $newwidth = $sizelim; $newheight = ($height / $width) * $newwidth; $anychanged = true;$width=$newwidth;$height=$newheight;
        }
        if ($height > $sizelim) {
            $newheight = $sizelim; $newwidth = ($width / $height) * $newheight; $anychanged = true;
        }
        if($anychanged){ $newimage = imagescale($image, $newwidth,$newheight, IMG_BICUBIC);
                         imagejpeg($newimage, $thumb_destination, $quality);imagedestroy($newimage); }
        imagedestroy($image);
    }
    function DoUpload(){
        if(!isset($_FILES['upload_file_name'])) return 0;
        if(!is_dir('images/thumb')) mkdir('images/thumb');
        if($_FILES['upload_file_name']['error']>0){
            return -1;
        }else{
            $fp = fopen('.la_lock',"w");
            while (!flock($fp, LOCK_EX| LOCK_NB)){
                usleep(10000);
            }
            $num=date('YmdHis');
            $base = 'images/'.$num;
            $thumb = 'images/thumb/'.$num;
            $ext=pathinfo($_FILES['upload_file_name']['name'],PATHINFO_EXTENSION);
            if($ext=='png') $ext='jpg';
            $final_path = $base.'.'.$ext; $final_thumb = $thumb.'.'.$ext; $i=0;
            while(file_exists($final_path)){
                $final_path = $base.strval($i).'.'.$ext; $final_thumb = $thumb.strval($i).'.'.$ext; $i++;
            }
            if($ext!='gif'){
                $this->CompressImage($_FILES['upload_file_name']['tmp_name'], $final_path, $final_thumb, 80,
                    (isset($_GET['compress'])&&$_GET['compress'])?800:1920);
            }else{
                move_uploaded_file($_FILES['upload_file_name']['tmp_name'], $final_path);
            }
            flock($fp, LOCK_UN);
            fclose($fp);
            $this->ReadImages(true);
            $this->WriteImages();
            echo '<uploaded>'.pathinfo($final_path,PATHINFO_BASENAME)."</uploaded>";
            exit;
            return 1;
        }
        return 0;
    }
    
    function &GetGallery($name){
        foreach($this->Galleries as &$g){
            if($g['name'] == $name) return $g;
        }
        return $this->NULL_GALLERY;
    }
    function EditGallery($name, $new_name=null, $delete=false, $do_rw=true){
        if($do_rw) $this->ReadImages();
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
        }
        if($do_rw) { $this->WriteImages(); $this->ClearData(); }
    }
    
    function ClearData(){
        $this->Posts = [];
        $this->Threads = [];
        $this->Images = [];
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
                $post['content'] = trim($m[3]);
                if(preg_match('/NEXT\s+([0-9]{14})\s*;/u', $m[2], $n)) $post['next'] = $n[1];
                if(preg_match('/PREV\s+([0-9]{14})\s*;/u', $m[2], $n)) $post['prev'] = $n[1];
                if(preg_match('/MDEL\s*;/u', $m[2]))                   $post['mark_delete'] = True;
                if(preg_match('/MVAL\s*([^;]+);/u', $m[2], $n))        $post['mark_value'] = trim($n[1]);
                if(preg_match('/REFS\s*([^;]+);/u', $m[2], $ma)){
                    $entries = [];
                    if(preg_match_all('/([0-9]{14})/u',$ma[1],$links,PREG_SET_ORDER)){ 
                        foreach($links as $l){
                            $entries[] = $l[1];
                        }
                        $post['refs'] = $entries;
                    }
                }
                /* marks add here */
                $this->Posts[] = $post;
            }
        }
    }
    
    function ReadPosts(){
        if ((!file_exists('la_config.md') || is_readable('la_config.md') == false) ||
            (!is_dir('posts') || is_readable('posts') == false) ||
            (!is_dir('images') || is_readable('images') == false) ||
            (!is_dir('styles') || is_readable('styles') == false)){
            $this->Install();
        }
        
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
        
        $this->DetectThreads();
    }
    
    function GetThreadForPost(&$post){
        if(isset($post['tid'])) return;
        if(!(isset($post['prev']) || isset($post['next']))) return;
        $th = [];
        $post['tid'] = &$th;
        $th['first'] = $th['last'] = &$post;
        $iterp = NULL; $count = 1;
        if(isset($post['prev']))for($p = $post['prev']; $p!=NULL; $p = $iterp){
            $np = &$this->GetPost($p);
            $np['tid'] = &$th;
            $th['first'] = &$np;
            $iterp = isset($np['prev'])?$np['prev']:NULL;
            $count++;
        }
        if(isset($post['next']))for($p = $post['next']; $p!=NULL; $p = $iterp){
            $np = &$this->GetPost($p);
            $np['tid'] = &$th;
            $th['last'] = &$np;
            $iterp = isset($np['next'])?$np['next']:NULL;
            $count++;
        }
        $th['count'] = $count;
        $this->Threads[] = $th;
    }
    
    function DetectThreads(){
        foreach($this->Posts as &$p){
            if(isset($p['tid'])) continue;
            $this->GetThreadForPost($p);
        }
        if(!isset($this->Threads) || !isset($this->Threads[0])) return;
        $now = date_timestamp_get(date_create());
        foreach($this->Threads as &$t){
            $lasttime = DateTime::createFromFormat('YmdHis', $t['last']['id']);
            $diff_days = ($now - date_timestamp_get($lasttime))/3600/24;
            $t['score'] = (float)$t['count']*0.2 - min($diff_days,200);
        }
        function cmp($a, $b){
            if ($a['score'] == $b['score']) return 0;
            return ($a['score'] > $b['score']) ? -1 : 1;
        }
        usort($this->Threads,"cmp");
    }
    
    function &GetPost($id){
        if(!isset($id)) return $this->NULL_POST;
        $i=0; $found=0;
        if(isset($this->Posts[0])) foreach($this->Posts as $p){
            if($p['id'] == $id) { $found = 1; break; }
            $i++;
        }
        if($found) return $this->Posts[$i];
        return $this->NULL_POST;
    }
    
    function WritePosts(){
        $cf = NULL;$opened =NULL;
        foreach($this->Posts as $p){
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
                    ((isset($p['mark_delete']) && $p['mark_delete'])?"MDEL; ":"").
                    ((isset($p['mark_value']) && $p['mark_value']>=0)?"MVAL {$p['mark_value']}; ":"").
                    ((isset($p['next']) && $p['next'])?"NEXT {$p['next']}; ":"").
                    ((isset($p['prev']) && $p['prev'])?"PREV {$p['prev']}; ":"").
                    ((isset($p['refs']) && isset($p['refs'][0]))?("REFS ".implode(" ",$p['refs'])."; "):"").
                    ']';
                    
            fwrite($opened, $info.PHP_EOL.PHP_EOL.$p['content'].PHP_EOL.PHP_EOL);
        }
    }
    
    function CachePostLinks(){
        if(isset($this->Posts) && isset($this->Posts[0]))foreach ($this->Posts as &$post){
            $this->ConvertPost($post);
            unset($post['refs']);
        }else return;
        if(isset($this->Images) && isset($this->Images[0])) foreach ($this->Images as &$im){
            unset($im['refs']);
        }
        foreach ($this->Posts as &$post){
            if(preg_match_all('/<a[^>]*href=[\'\"]\?post=([0-9]{14})[\'\"]>.*<\/a>/u',$post['html'],$matches,PREG_SET_ORDER)){
                foreach($matches as $m){
                    $ref = &$this->GetPost($m[1]);
                    if($ref!=NULL){
                        if(!isset($ref['refs']))$ref['refs']=[];
                        if(!in_array($post['id'],$ref['refs'])){ $ref['refs'][]=$post['id']; }
                    }
                }
            }
            if(preg_match_all('/!\[([^\]]*)\]\(images\/([0-9]{14,}\.(jpg|jpeg|png|gif))\)/u', $post['content'],$matches,PREG_SET_ORDER)){
                foreach($matches as $m){  
                    if(($im = &$this->FindImage($m[2]))!=NULL){
                        if(!isset($im['refs'])){$im['refs']=[];}
                        if(!in_array($post['id'], $im['refs']))$im['refs'][] = $post['id'];
                    }
                }
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
    
    function &EditPost($id_if_edit, $content, $mark_delete, $reply_to, $get_original_only=false, $mark_value=NULL){
        $this->ReadImages();
        $this->ReadPosts();
        $p_success = NULL;
        if(isset($id_if_edit)){
            $post = &$this->GetPost($id_if_edit);
            if($post===$this->NULL_POST) return $this->NULL_POST;
            if($get_original_only){
                return $post['content'];
            }
            if(isset($content)) $post['content'] = $content;
            if(isset($mark_delete)) $post['mark_delete'] = $mark_delete;
            if(isset($mark_value)) $post['mark_value'] = $mark_value;
            $p_success = &$post;
        }else{
            if(!isset($content)) return $this->NULL_POST;
            $id = date('YmdHis');
            if($this->GetPost($id)!== $this->NULL_POST) return $this->NULL_POST;
            $post = [];
            $post['id'] = $id;
            $post['content'] = $content;
            if(isset($reply_to) && ($rep = &$this->GetPost($reply_to))!== $this->NULL_POST){
                if(!(isset($rep['next']) && $rep['next'])){$rep['next'] = $id; $post['prev'] = $rep['id'];}
                else $post['content'] = "[引用的文章]($reply_to)".$post['content'];
            }
            $this->Posts[] = $post;
            $p_success = &$this->Posts[count($this->Posts) - 1];
        }
        $this->CachePostLinks();
        $this->WritePosts();
        $this->WriteImages();
        $this->ClearData();
        return $p_success;
    }
    
    function InsertReplacementSymbols($MarkdownContent){
        $replacement = preg_replace('/<!--[\s\S]*-->/U',"",$MarkdownContent);
        $replacement = preg_replace_callback("/(```|`)([^`]*)(?1)/U",
                    function($matches){
                        $rep = preg_replace('/->/','-@>',$matches[0]);
                        $rep = preg_replace('/=>/','=@>',$rep);
                        $rep = preg_replace('/<=/','<@=',$rep);
                        $rep = preg_replace('/<-/','<@-',$rep);
                        $rep = preg_replace('/\R([+]{3,})\R/',PHP_EOL.'@$1'.PHP_EOL,$rep);
                        $rep = preg_replace('/\[-/','[@-',$rep);
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
        $replacement = preg_replace_callback("/(```|`)([^`]*)(?1)/U",
                    function($matches){
                        $rep = preg_replace('/-@>/','->',$matches[0]);
                        $rep = preg_replace('/<@-/','<-',$rep);
                        $rep = preg_replace('/=@>/','=>',$rep);
                        $rep = preg_replace('/<@=/','<=',$rep);
                        $rep = preg_replace('/\R@([+]{3,})\R/',PHP_EOL.'$1'.PHP_EOL,$rep);
                        $rep = preg_replace('/\[@-/','[-',$rep);
                        return $rep;
                    },
                    $replacement);
        return $replacement;
    }
    
    function ProcessRequest(&$message=NULL, &$redirect=NULL){
        if(isset($_GET['set_language'])){
            setcookie('la_language',$_GET['set_language']); $_COOKIE['la_language'] = $_GET['set_language'];
            $redirect='index.php';return 0;
        }
        if(isset($_GET['post'])){
            $this->CurrentPostID = $_GET['post'];
        }
        if(isset($_GET['offset'])){
            $this->CurrentOffset = $_GET['offset'];
        }
        if(isset($_GET['part'])){
            if($_GET['part'] == 'hot') $this->ExtraScripts.="ShowLeftSide();";
            else if ($_GET['part'] == 'recent') $this->ExtraScripts.="ShowCenterSide();";
        }
        if(isset($_GET['post'])){
            $this->ExtraScripts.="ScrollToPost('".$_GET['post']."');";
        }
        if(isset($_GET['image_info'])){
            $m=$_GET['image_info'];
            $this->ReadImages();
            $this->ReadPosts();
            $im = &$this->FindImage($m);
            if($im==NULL || !isset($im['refs']) || !isset($im['refs'][0])){ echo "not_found"; exit; }
            echo "<ref>".sizeof($im['refs'])."</ref>";
            echo "<insert><ul>";
            foreach($im['refs'] as $ref){
                $this->MakeSinglePost($this->GetPost($ref), false, true, "post_preview", true, false, false, false, true);"</li>";
            }
            echo "</ul></insert>";
            exit;
        }
        if($this->LoggedIn){
            $this->DoUpload();
            
            if(isset($_POST['settings_button'])){
                if(isset($_POST['settings_title'])) $this->Title=$_POST['settings_title'];
                if(isset($_POST['settings_short_title'])) $this->ShortTitle=$_POST['settings_short_title'];
                if(isset($_POST['settings_display_name'])) $this->DisplayName=$_POST['settings_display_name'];
                if(isset($_POST['settings_special_navigation'])) $this->SpecialNavigation=$_POST['settings_special_navigation'];
                if(isset($_POST['settings_special_footer'])) $this->SpecialFooter=$_POST['settings_special_footer'];
                if(isset($_POST['settings_special_footer2'])) $this->SpecialFooter2=$_POST['settings_special_footer2'];
                if(isset($_POST['settings_special_pinned'])) $this->SpecialPinned=$_POST['settings_special_pinned'];
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
                return 0;
            }
            if(isset($_GET['post'])){
                if(isset($_GET['post_original'])){
                    echo $this->EditPost($_GET['post'],NULL,false,NULL,true,NULL);
                    exit;
                }
            }
            if(isset($_GET['mark_delete']) && isset($_GET['target'])){
                $this->EditPost($_GET['target'],NULL,$_GET['mark_delete']=='true',NULL,false,NULL);
                if(isset($_GET['post'])) $redirect='?post='.$_GET['target']; else $redirect='index.php';
                return 0;
            }
            if(isset($_GET['set_mark']) && isset($_GET['target'])){
                $this->EditPost($_GET['target'],NULL,NULL,NULL,NULL,$_GET['set_mark']);
                if(isset($_GET['post'])) $redirect='?post='.$_GET['target']; else $redirect='index.php';
                return 0;
            }
            if(isset($_POST['post_button']) && isset($_POST['post_content'])){
                $c = $_POST['post_content'];
                if('有什么想说的' == $c){ return 0;}
                if(preg_match('/\[LAMDWIKIPOST/u',$c))
                    { $message='Can\'t use character sequence"[LAMDWIKIPOST" anywhere in the post...'; return 1; }
                $reply_to = (isset($_POST['post_reply_to'])&&$_POST['post_reply_to']!="")?$_POST['post_reply_to']:NULL;
                $edit_id = (isset($_POST['post_edit_target'])&&$_POST['post_edit_target']!="")?$_POST['post_edit_target']:NULL;
                if(($edited = $this->EditPost($edit_id, $c, NULL, $reply_to,NULL,NULL))!=NULL){
                    $redirect='?post='.$edited['id'];
                    return 0;
                };
            }
            if(isset($_POST['gallery_edit_confirm']) && isset($_POST['gallery_edit_new_name']) && $_POST['gallery_edit_new_name']!=''){
                $old_name = isset($_POST['gallery_edit_old_name'])?$_POST['gallery_edit_old_name']:"";
                $new_name = $_POST['gallery_edit_new_name'];
                if($old_name!=''){
                    $this->EditGallery($old_name, $new_name, false, true);
                    $redirect='?gallery='.$new_name;
                }else{
                    $this->EditGallery(null, $new_name, false, true);
                    if(isset($_GET['gallery'])) $redirect='?gallery='.$_GET['gallery']; else $redirect='index.php';
                }
                return 0;
            }
            if(isset($_GET['gallery_edit_delete'])&&$_GET['gallery_edit_delete']!=null){
                $this->EditGallery($_GET['gallery_edit_delete'], null, true, true);
                if(isset($_GET['gallery'])) $redirect='?gallery=main'; else $redirect='index.php';
                return 0;
            }
            if(isset($_POST['gallery_move_ops'])&&isset($_POST['gallery_move_ops'])!=null){
                if(preg_match('/^(REM|ADD)\s+(\S+)\s+(.*)$/u', $_POST['gallery_move_ops'], $ma)){
                    $this->ReadImages();
                    if(preg_match_all('/(\S+)/u', $ma[3], $files, PREG_SET_ORDER)) foreach($files as $name){
                        $this->EditImage($name[1], $ma[2], ($ma[1]=='REM'));
                    }
                    $this->WriteImages();
                    $this->ClearData();
                }
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
            if(isset($_GET['rewrite_styles'])){
                $this->WriteStyles();
                $redirect='?extras=true'; return 0;
            }
        }
        return 0;
    }
    
    function PostProcessHTML($html,&$added_images=null){
        $html = preg_replace("/(<a[^>]*href=[\'\"])([0-9]{14})([\'\"][^>]*>)(.*?<\/a>)/u","$1?post=$2$3$4",$html);
        $images = [];
        $images_noclick = [];
        $html = preg_replace_callback("/(<img[^>]*src=[\'\"])(images\/([0-9]{14,}\.(jpg|png|jpeg|gif)))([\'\"][^>]*)>/u",
                    function($m) use (&$images,&$images_noclick) {
                        $src = $m[3];
                        if(($im = &$this->FindImage($m[3]))!=NULL && isset($im['thumb'])){ 
                            $src = $im['thumb'];
                        }
                        $images[]=$m[1].$src.$m[5]." data-imgsrc='".$m[3]."'>";
                        $images_noclick[]=$m[1].$src.$m[5].">";
                        return "";
                    },$html,-1,$count);
        if($count){
            $added_images = $images_noclick;
            $html = preg_replace("/<p>[\s]*<\/p>/u","",$html);
            if($count==1){$html.=$images[0];}
            else{
                $html.="<div class='p_row'>";
                foreach($images as $img){
                    $html.="<div class='p_thumb'>".$img."</div>";
                }
                $html.="<div class='p_thumb' style='flex-grow:10000;box-shadow:none;height:0;'></div></div>";
            }
        }else{
            $added_images = NULL;
        }
        return $html;
    }
    
    function ConvertPost(&$post){
        if(!isset($post['html'])){
            $post['html'] = $this->PostProcessHTML($this->PDE->text($this->InsertReplacementSymbols($post['content'])),$post['images']);
        }
    }
    
    function DetectPageType(){
        if(isset($_GET['extras'])) $this->PageType='extras';
        else if(isset($_GET['settings'])) $this->PageType='settings';
        else if(isset($_GET['gallery'])) $this->PageType='gallery';
        else if(isset($this->CurrentPostID)) $this->PageType = "post";
        else $this->PageType = "main";
    }
    
    function MakeHeader(){?>
        <!DOCTYPE html><html lang='zh-Hans-CN'>
        <head>
        <meta charset='utf-8'>
        <meta content="width=device-width, initial-scale=1" name="viewport">
        <title><?=$this->T($this->Title)?></title>
        <link href='styles/main.css' rel='stylesheet' type="text/css">
        </head>
        <div class='page'>
        <script type='text/javascript'>
            function toggle_mobile_show(a){a.classList.toggle('hidden_on_mobile')}
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
            function ShowBackdrop(alpha=0.2){
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
                ShowBackdrop(0.2);
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
            function ScrollToPost(id){
                if(!(post = document.querySelector("[data-post-id='"+id+"']"))) return;
                post.scrollIntoView({ behavior: 'smooth', block: 'start'});
            }
        </script>
        <header>
            <?php $this->MakeNavButtons(); ?>
            <hr />
        </header>
        <div id='post_menu' style='display:none;' class='pop_menu smaller invert_a'>
            <ul>
            <li><span id='_time_hook' class='smaller gray'>时间</span>&nbsp;&nbsp;<a href='javascript:HidePopMenu();'>×</a></li>
            <li>
                <a id='share_copy'>📋︎</a>
                <a id='share_pin' target='_blank'>Pin</a>
                <a id='share_twitter' target='_blank'>Twitter</a>
                <a id='share_weibo' target='_blank'><?=$this->T('微博')?></a>
            </li>
            <?php if($this->LoggedIn){ ?>
                <hr />
                <li><a id='menu_edit'><?=$this->T('修改')?></a></li>
                <li>   
                    <a id='menu_refer_copy'><?=$this->T('只复制')?></a>
                    <a id='menu_refer'><?=$this->T('引用')?></a><br class='hidden_on_desktop' />
                </li>
                <li><a id='menu_mark' href='javascript:ToggleMarkDetails()'><?=$this->T('标记')?></a></li>
                <li id='mark_details' style='display:none;'><b>
                    <a id='mark_set_clear' href='javascript:SetMark(-1);'>_</a>
                    <a id='mark_set_0' href='javascript:SetMark(0);'><?=$this->Markers[0]?></a>
                    <a id='mark_set_1' href='javascript:SetMark(1);'><?=$this->Markers[1]?></a>
                    <a id='mark_set_2' href='javascript:SetMark(2);'><?=$this->Markers[2]?></a>
                    <a id='mark_set_3' href='javascript:SetMark(3);'><?=$this->Markers[3]?></a>
                    <a id='mark_set_4' href='javascript:SetMark(4);'><?=$this->Markers[4]?></a>
                </b></li>
                <hr />
                <li><a id='menu_delete' class='smaller'></a></li>
            <?php } ?>
            </ul>
        </div>
        <div id='backdrop' style='display:none;' class='backdrop' onclick='HideRightSide()'
            ondrop="_dropHandler(event);" ondragover="_dragOverHandler(event);"></div>
        <div id='pop_right' class='pop_right'>
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
    <?php
    }
    
    function TranslatePostParts($html){
        $html = preg_replace_callback('/>([^<]+?)</u', function($ma){
                return ">".$this->T($ma[1])."<";
            }, $html);
        return $html;
    }
    
    function MakeNavButtons(){?>
        <b><a href='index.php' class='hidden_on_mobile'><?=$this->T($this->Title)?></a></b>
        <b><a class='hidden_on_desktop'
            href='javascript:toggle_mobile_show(document.getElementById("mobile_nav"))'><?=$this->T($this->ShortTitle)?></a></b>
        <?php if($this->PageType=='post'){ ?>
            <div style='display:inline;'><a id='button_back'>←</a></div>
            <div style='float:right;'>
                <span class='hidden_on_desktop'><a id='button_ref' href='javascript:ToggleLeftSide();'><?=$this->T('链接')?></a></span>
                <span class='hidden_on_wide'>
                    <a id='button_toc'
                        href='javascript:ShowRightSide(true,document.querySelector("#div_right"));'><?=$this->T('目录')?></a></div></span>
        <?php } ?>
        <ul class='hidden_on_mobile' id='mobile_nav'>
        <?php if($this->PageType!='main'){ ?>
            <li class='hidden_on_desktop block_on_mobile' id='button_recent'><a href='index.php?part=recent'><?=$this->T('最近')?></a></li>
            <li class='hidden_on_desktop block_on_mobile' id='button_hot'><a href='index.php?part=hot'><?=$this->T('热门')?></a></li>
        <?php } else { ?>
            <li class='hidden_on_desktop block_on_mobile' id='button_recent'>
                <a href='javascript:ShowCenterSide();toggle_mobile_show(document.getElementById("mobile_nav"));'><?=$this->T('最近')?></a></li>
            <li class='hidden_on_desktop block_on_mobile' id='button_hot'>
                <a href='javascript:ShowLeftSide();toggle_mobile_show(document.getElementById("mobile_nav"));'><?=$this->T('热门')?></a></li>
        <?php } ?>
            <?php $this->SpecialNavigation;if(isset($this->SpecialNavigation) && ($p = &$this->GetPost($this->SpecialNavigation))!=NULL){
                echo $this->TranslatePostParts($this->GenerateSinglePost($p, false, false, false, false));
            } ?>
            <li><a href='<?=$this->PageType!='gallery'?"?gallery=main":"#"?>'><?=$this->T('媒体')?></a></li>
            <?php if($this->LanguageAppendix=='zh'){ ?>
                <li class='invert_a smaller'><a href='<?='index.php?&set_language=en'?>'><b>汉语</b>/English</a></li>
            <?php }else { ?>
                <li class='invert_a smaller'><a href='<?='index.php?&set_language=zh'?>'>汉语/<b>English</b></a></li>
            <?php } ?>
        </ul>
    <?php
    }
    
    function GenerateLinkedPosts($ht){
        $ht = preg_replace_callback('/<p>[\s]*<a[^>]*href=[\'\"]\?post=([0-9]{14})[\'\"]>(.*)<\/a>[\s]*<\/p>/u',
            function($m){
                $rp = &$this->GetPost($m[1]);
                $s="<a href='?post=".$m[1]."' class='clean_a'><div class='post_ref post_box'>".
                    "<div class='smaller gray'>".$m[2]."</div><div class='post_ref_inner'>".
                    ($rp!==NULL?$this->GenerateSinglePost($rp,true,false,false,true):$this->T("未找到该引用。")).
                    "</div></div></a>";
                return $s;
            },
            $ht
        );
        return $ht;
    }

    function GenerateSinglePost(&$post, $strip_tags, $generate_anchor=false, $generate_refs=false, $generate_thumbs=false){
        $this->ConvertPost($post);
        if($generate_anchor){ $this->CreatePostAnchor($post); }
        $ht = $post['html'];
        if ($strip_tags){
            $ht = strip_tags($ht,'<b><i><h1><h2><h3><h4><p><blockquote>');
            $ht = "<div class='post_ref_main'>".$ht."</div>";    
        }
        else if($generate_refs) $ht = $this->GenerateLinkedPosts($ht);
        if ($generate_thumbs && isset($post['images']) && isset($post['images'][0])){
            $ht.="<div class='post_ref_images'><ul class='side_thumb ref_thumb'>";
            foreach($post['images'] as $im){
                $ht.="<li class='file_thumb'>".$im."</li>";
            }
            $ht.="</ul></div>";
        }
        return $ht;
    }
    
    function MakeSinglePost(&$post, $show_link=false, $side=false, $extra_class_string=NULL,
                                    $strip_tags=false, $show_thread_link=false, $show_reply_count=false, $generate_anchor=false,
                                    $generate_thumb = false, $is_top = false){
        $is_deleted = (isset($post['mark_delete'])&&$post['mark_delete']);
        $mark_value = isset($post['mark_value'])?$this->Markers[$post['mark_value']]:-1;
        $ref_count = isset($post['refs'])?sizeof($post['refs']):0;
        ?>
        <li class='post<?=isset($extra_class_string)?' '.$extra_class_string:''?><?=$side?" post_box":""?>'
            data-post-id='<?=$post['id']?>' <?=$is_deleted?"data-mark-delete='true'":""?>>
            <?php if($mark_value>=0 && !$show_link){?>
                <div style='font-size:1rem;' class='<?=$is_deleted?"gray":""?>'><?=$this->Markers[$post['mark_value']]?>
                    <?=$this->T('标记')?></div>
            <?php } ?>
            <?php if($is_top){?>
                <div class='top_post_hint'><?=$this->T('置顶帖子')?><hr /></div>
            <?php } ?>
                <?=$side?"<a href='?post={$post['id']}'>":""?>
            <div class='<?=$side?"":($show_link?'post_width':'post_width_big')?>
                <?=$is_deleted?"deleted_post":""?>'>
                    <?php if(!$side && !$strip_tags){?>
                        <div class='post_menu_button _menu_hook' >+</div>
                    <?php } ?>
                    <?=$this->GenerateSinglePost($post, $strip_tags, $generate_anchor, true, $generate_thumb)?>
            </div>
            <?=$side?"</a>":""?>
            <?php if(!$side && $show_link){ ?>
                <a href='?post=<?=$post['id']?>'>
                <div class='post_access <?=($mark_value<0 || $ref_count)?"invert_a":""?> hover_dark'>
                    <?=isset($post['mark_value'])?$this->Markers[$post['mark_value']]:($ref_count?"":"→")?>
                </div>
                <?php if($ref_count){ ?>
                    <div class='post_access ref_count'><?=$ref_count?></div>
                <?php } ?>
                </a>
            <?php }
                if($show_thread_link && isset($post['tid']) && $post['tid']['first']['id']!=$post['id']){ ?>
                    <a href='?post=<?=$post['tid']['first']['id']?>' class='invert_a smaller block opt_compact'>
                    <div class='post_reply'><div class='smaller'><?=$this->T('回复给主题帖：')?></div>
                        <?=$this->GenerateSinglePost($post['tid']['first'], true, false, false, true);?>
                    </div>
                    </a>
                <?php }
                if($show_reply_count && isset($post['tid'])){ ?>
                    <a class='smaller block invert_a' href='?post=<?=$post['tid']['last']['id']?>'><?=$post['tid']['count']?>
                        <?=$this->T('个回复')?></a>
                <?php }
            ?>    
        </li>
    <?php
    }
    
    function MakePostingFields($reply_to=NULL, $show_hint=false){?>
        <div class='smaller' id='post_hint_text' style='display:<?=$show_hint?"block":"none"?>;'><?=$this->T('继续补充该话题：')?></div>
        <form action="<?=$_SERVER['REQUEST_URI']?>" method="post" style='display:none;' id='post_form'></form>
        <textarea id="post_content" name="post_content" rows="4" form='post_form'
                  onfocus="if (value =='<?=$this->T('有什么想说的')?>'){value ='';}la_auto_grow(this);"
                  onblur="if (value ==''){value='<?=$this->T('有什么想说的')?>';la_auto_grow(this);}"    
                  oninput="la_auto_grow(this);" onload="la_auto_grow(this);"><?=$this->T('有什么想说的')?></textarea>
        <input class='button' form="post_form" type="submit" name='post_button' value=<?=$this->T('发送')?> /></input>
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
    
    function MakeRecentPosts(){?>
        <div class='center' id='div_center'>
            <h2><?=$this->T('最近')?></h2>
            <?php if($this->LoggedIn){ ?>
                <div class='post_box_top _input_bundle'>
                    <?php $this->MakePostingFields(NULL,false); ?>
                </div>
            <?php } ?>
            <ul>
                <?php
                    if(isset($this->SpecialPinned) && ($p = &$this->GetPost($this->SpecialPinned))!=NULL && !$this->CurrentOffset){
                        $this->MakeSinglePost($p, true, false, false, false, true, false, false, false, true);
                    }
                    $i = 0;
                    foreach(array_reverse($this->Posts) as &$p){
                        if($i < $this->PostsPerPage * $this->CurrentOffset) {$i++; continue;}
                        if(in_array($p['id'],[$this->SpecialPinned,$this->SpecialFooter,$this->SpecialFooter2,$this->SpecialNavigation]))
                            continue;
                        if(isset($p['tid'])){
                            if(isset($p['tid']['displayed'])) continue;
                            $p['tid']['displayed'] = True;
                        }
                        $this->MakeSinglePost($p, true, false, NULL, false, true, false, false, false, false);
                        $i++;
                        if($i >= $this->PostsPerPage * (1+$this->CurrentOffset)) {break;}
                    }?>
            </ul>
            <div class='page_selector clean_a'>
                <hr />
                <a <?=$this->CurrentOffset>0?("href='index.php?offset=".($this->CurrentOffset-1)."'"):""?>
                   <?=$this->CurrentOffset==0?"class='gray'":""?>>←</a>
                <?=$this->CurrentOffset+1?>
                <a href='index.php?offset=<?=$this->CurrentOffset+1?>'>→</a>
            </div>
        </div>
    <?php
    }
    
    function MakeHotPosts(){?>
        <div class='left hidden_on_mobile' id='div_left'>
            <h2><?=$this->T('热门')?></h2>
            <ul>
                <?php
                    $i=0;
                    foreach($this->Threads as &$th){
                        if($i>=$this->HotPostCount) break;
                        $this->MakeSinglePost($th['first'], false, true, "post_preview", true, false, true, false, true, false);
                        $i++;
                    } ?>
            </ul>
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
                <ul><?php
                foreach($p['refs'] as &$pr){
                    $this->MakeSinglePost($this->GetPost($pr), false, true, "post_preview", true, false, false, false, true, false);
                } 
                ?></ul>
            <?php }else{ ?>
                <span class='gray smaller'><?=$this->T('没有帖子链接到这里。')?></span>
            <?php } ?>
        </div>
    <?php
    }
    
    function MakePostSection(&$post){
        $this->Anchors = [];
        ?>
        <div class='center' id='div_center'>
            <?php $th=NULL; $is_thread = isset($post['tid']); if($is_thread){ $th = $post['tid'];?>
                <h2><?=$this->T('话题')?></h2>
            <?php }else{ ?>
                <h2><?=$this->T('详细')?></h2>
            <?php } ?>
            <ul>
            <?php
                if($is_thread){
                    for($p = &$th['first']; $p!=$this->NULL_POST; $p = &$this->GetPost(isset($p['next'])?$p['next']:NULL)){
                        $use_class=($p == $post)?'focused_post':'';
                        $show_link = ($p == $post)?false:true;
                        $this->MakeSinglePost($p,$show_link,false,$use_class,false, false, false, true, false, false);
                        if($p == $post){?>
                        <script>
                        document.title+=" | <?=addslashes(preg_replace('/\r|\n/u', ' ', mb_substr(strip_tags($post['html']),0,1000)))?>";
                        </script>
                        <?php }
                    }
                }else{
                    $this->MakeSinglePost($post,false, false, 'focused_post',false, false, false, true, false, false);
                    ?><script>
                    document.title+=" | <?=addslashes(preg_replace('/\r|\n/u', ' ', mb_substr(strip_tags($post['html']),0,1000)))?>";
                    </script><?php
                } ?>
            </ul>
            <?php if($this->LoggedIn){ ?>
                <br />
                <div class='post_width_big'>
                    <?php $this->MakePostingFields($is_thread?$th['last']['id']:$post['id'], true);?>
                </div>
            <?php } ?>
        </div>
    <?php
    }
    
    function MakeSideGalleryCode(){?>
        <div>
            <h3><?=$this->T('点击图片以插入：')?></h3>
            <select id="side_gallery_select" onchange="RefreshSideGallery()">
                <option value="main"><?=$this->T('全部')?></option>
                <option value="trash"><?=$this->T('垃圾桶')?></option>
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
                                    str+="<li><div class='file_thumb' onclick='InsertImage(\""+m[1]+"\")'>"+
                                        "<img src='"+m[2]+"' /></div>"
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
        <div id='upload_operation_area'>
            <p><?=$this->T('选择、粘贴或者拖动到页面以上传图片。')?></p>
            <input type="file" id='upload_selector' accept="image/x-png,image/gif,image/jpeg" multiple/>
            <ul id='file_list'>
            </ul>
            <div class='smaller gray' id='upload_hint'><?=$this->T('就绪')?></div>
        </div>
        <a id='upload_click' href='javascript:UploadList()'<?=$is_side?" data-is-side='true'":""?>><?=$this->T('上传列表中的文件')?></a>
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
            let _fd_list = [];  
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
                        document.onpaste=pastehandler;
                        RefreshSideGallery();
                    }
                }
            }
            function UploadList(){
                if(!_fd_list.length) return;
                hint.innerHTML="<?=$this->T('正在上传...')?>";
                list=document.querySelector('#upload_operation_area');
                list.style.pointerEvents='none';
                list.style.opacity='0.5';
                document.onpaste="";
                for(i=0;i<_fd_list.length;i++){
                    let xhr = new XMLHttpRequest();
                    //xmlHTTP.upload.addEventListener("loadstart", loadStartFunction, false);
                    //xmlHTTP.upload.addEventListener("progress", progressFunction, false);
                    //xmlHTTP.addEventListener("load", transferCompleteFunction, false);
                    //xmlHTTP.addEventListener("error", uploadFailed, false);
                    //xmlHTTP.addEventListener("abort", uploadCanceled, false);
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
              ev.preventDefault();
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
                bkg.style.display="block";
                console.log('File(s) in drop zone');
                ev.preventDefault();
            }
        </script>
    <?php
    }
    
    function MakeGallerySection(){
        if(!isset($_GET['gallery'])) return; 
        $name=NULL; if($_GET['gallery']!='main' && $_GET['gallery']!='trash'){
            $name=$_GET['gallery'];}?>
        <script>
        document.title+=" | <?=$this->T('画廊')?>";
        </script>
        <div class='center' id='div_center' style='position:relative;'>
            <h2><?=(isset($name) && $this->GetGallery($name)!=NULL)?("<span class='gray'>".$this->T('相册').":</span>".$name):
                                                                ($_GET['gallery']=='trash'?$this->T('垃圾桶'):$this->T('画廊'))?></h2>
            <div class='hidden_on_desktop'>
                <select id="gallery_go_to" onchange="window.location.href='?gallery='+this.value;">
                <option value="main"><?=$this->T('全部')?></option>
                <?php if(isset($this->Galleries[0])) foreach($this->Galleries as $g){ ?>
                    <option value="<?=$g['name']?>" <?=$_GET['gallery']==$g['name']?"selected":""?>><?=$g['name']?></option>
                <?php } ?>
                </select>
            </div>
            <?php if($this->LoggedIn){?>
                <div>
                    <?php if(isset($name)){ ?>
                        <div style='text-align:right;position:absolute;right:0;top:0;width:100%;' class='invert_a smaller'>
                            <a href='javascript:ShowDeleteMenu();'  class='smaller'><?=$this->T('删除相册')?></a>
                            <div class='pop_menu smaller invert_a' id='gallery_delete_menu' style='display:none;'>
                                <div style='float:left;' class='gray'><?=$this->T('该操作不删除图片。')?></div>
                                <a href='javascript:HidePopMenu();'>×</a>
                                <hr />
                                <a href='?gallery=main&gallery_edit_delete=<?=$_GET['gallery']?>'><b><?=$this->T('删除相册')?></b></a>
                            </div>
                        </div>
                    <?php } ?>
                    <?php $this->MakeUploader(false); ?>
                    <div style='text-align:right;position:relative;' class='invert_a smaller'>
                        <div style='position:relative'>
                        <?php if(isset($name)){ ?>
                            <a href='javascript:ShowGalleryEditMenu("<?=$name?>")'><?=$this->T('改名')?></a>
                        <?php } ?>
                            <a href='javascript:ShowGalleryEditMenu(null)'><?=$this->T('添加')?></a>
                            <div class='pop_menu smaller invert_a' id='gallery_edit_menu' style='display:none;max-width:90%;'>
                                <form action="<?=$_SERVER['REQUEST_URI']?>&edit_gallery=true"
                                    method="post" style='display:none;' id='gallery_edit_form'></form>
                                <a style='float:left;'><?=$this->T('相册名字：')?></a>
                                <a href='javascript:HidePopMenu();'>×</a>
                                <input type='input' form='gallery_edit_form' name='gallery_edit_new_name' id='gallery_edit_new_name'>
                                <input type='input' form='gallery_edit_form' name='gallery_edit_old_name'
                                    id='gallery_edit_old_name' style='display:none'>
                                <input class='button' type='submit' form='gallery_edit_form'
                                    name='gallery_edit_confirm' id='gallery_edit_confirm' value='<?=$this->T('确认')?>'></a>
                            </div>
                        </div>
                    </div>
                    <div class='smaller'>
                        <form action="<?=$_SERVER['REQUEST_URI']?>"
                            method="post" style='display:none;' id='gallery_move_form'></form>
                        <input type='input' form='gallery_move_form' name='gallery_move_ops'
                            id='gallery_move_ops' style='display:none'>
                        <p><?=$this->T('选择了')?> <span id='gallery_selected_count'>0</span> <?=$this->T('个图片。')?>
                            <a href='javascript:ClearSelectedImages()'><?=$this->T('清除')?></a></p>
                        <p><?=$this->T('添加到')?>
                            <select id="gallery_move_to">
                            <option value="trash"><?=$this->T('垃圾桶')?></option>
                            <?php if(isset($this->Galleries[0])) foreach($this->Galleries as $g){ ?>
                                <option value="<?=$g['name']?>"><?=$g['name']?></option>
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
            <div>
                <div class='p_row'>
                <?php if(isset($this->Images[0])) foreach($this->Images as $im){
                    if($_GET['gallery']=='trash') $name='trash';
                    if($_GET['gallery']!='main'){ if(!isset($im['galleries']) || !in_array($name, $im['galleries'])) continue;} ?>
                    <div class='p_thumb'>
                        <?php if($this->LoggedIn){ ?>
                            <div class="post_menu_button _select_hook white" onclick='ToggleSelectImage(this, "<?=$im["name"]?>")'>●</div>
                        <?php } ?>
                        <img src='<?=$im['thumb']?>' data-imgsrc='<?=$im["name"]?>' />
                    </div>
                <?php } ?>
                <div class='p_thumb' style='flex-grow:10000;box-shadow:none;height:0;'></div>
                </div>
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
                m.style.display='block';
                if(old_name!=''){ old.value=old_name; }else{ old.value=''; }
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
        <div class='left hidden_on_mobile' id='div_left'>
            <h2><?=$this->T('相册')?></h2>
            <div>
                <ul>
                    <a href='?gallery=main'><li class='post post_box<?=$_GET['gallery']=='main'?' bold':' gray'?>'>
                        <?=$this->T('全部图片')?></li></a>
                    <?php if(isset($this->Galleries[0])) foreach($this->Galleries as $g){ ?>
                        <a href='?gallery=<?=$g['name']?>'>
                            <li class='post post_box<?=$g['name']==$_GET['gallery']?' bold':' gray'?>'>
                                <?=$g['name']?>
                            </li></a>
                    <?php } ?>
                    <?php if($this->LoggedIn){ ?>
                        <a href='?gallery=trash'><li class='post post_box<?=$_GET['gallery']=='trash'?' bold':' gray'?>'>
                            <?=$this->T('垃圾桶')?></li></a>
                    <?php } ?>
                </ul>
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
                        <li class='toc_entry_<?=$a[0]>5?5:$a[0]?>'><a href='#<?=$a[1]?>'><?=$a[2]?></a></li>
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
                        <td><input type="input" form="settings_form" id='settings_title' name='settings_title'
                            value='<?=$this->Title?>'/></td></tr>
                    <tr><td><?=$this->T('短标题')?></td>
                        <td><input type="input" form="settings_form" id='settings_short_title' name='settings_short_title'
                            value='<?=$this->ShortTitle?>'/></td></tr>
                    <tr><td><?=$this->T('显示名称')?></td>
                        <td><input type="input" form="settings_form" id='settings_display_name' name='settings_display_name'
                        value='<?=$this->DisplayName?>'/></td></tr>
                    <tr><td><?=$this->T('导航栏')?>
                        <?=isset($this->SpecialNavigation)?"<a href='?post=".$this->SpecialNavigation."'>→</a>":""?></td>
                        <td><input type="input" form="settings_form" id='settings_special_navigation' name='settings_special_navigation'
                        value='<?=$this->SpecialNavigation?>'/></td></tr>
                    <tr><td><?=$this->T('脚注')?> 1<?=isset($this->SpecialFooter)?"<a href='?post=".$this->SpecialFooter."'>→</a>":""?></td>
                        <td><input type="input" form="settings_form" id='settings_special_footer' name='settings_special_footer'
                        value='<?=$this->SpecialFooter?>'/></td></tr>
                    <tr><td><?=$this->T('脚注')?> 2<?=isset($this->SpecialFooter2)?"<a href='?post=".$this->SpecialFooter2."'>→</a>":""?></td>
                        <td><input type="input" form="settings_form" id='settings_special_footer2' name='settings_special_footer2'
                        value='<?=$this->SpecialFooter2?>'/></td></tr>
                    <tr><td><?=$this->T('置顶文')?><?=isset($this->SpecialPinned)?"<a href='?post=".$this->SpecialPinned."'>→</a>":""?></td>
                        <td><input type="input" form="settings_form" id='settings_special_pinned' name='settings_special_pinned'
                        value='<?=$this->SpecialPinned?>'/></td></tr>
                    <tr><td><?=$this->T('附加操作')?></td><td><a class='gray' href='index.php?extras=true'><?=$this->T('进入')?></a></td></tr>
                    <tr><td class='smaller gray'>&nbsp;</td></tr>
                    <tr><td class='smaller gray'><?=$this->T('管理员')?></td><td class='smaller'>
                        <a href='index.php?logout=true'><?=$this->T('登出')?></a></td></tr>
                    <tr><td><?=$this->T('帐号')?></td>
                        <td><input type="input" form="settings_form" id='settings_id' name='settings_id'
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
                        <td><input type="input" form="login_form" id='login_id' name='login_id' /></td></tr>
                    <tr><td><?=$this->T('密码')?></td>
                        <td><input type="password" form="login_form" id='login_password' name='login_password' /></td></tr>
                <?php } ?>
            </table>
            <?php if($this->LoggedIn){ ?>
                <input class='button' form="settings_form" type="submit" name='settings_button' value='<?=$this->T('保存设置')?>' /></input>
            <?php }else{ ?>
                <input class='button' form="login_form" type="submit" name='login_button' value='<?=$this->T('登录')?>' /></input>
            <?php } ?>
        </div>
    <?php
    }
    
    function MakeExtraOperations(){?>
        <div class='settings'>
            <h3><?=$this->T('附加操作')?></h3>
            <a href='?index.php&settings=true'><?=$this->T('返回一般设置')?></a>
            <p>&nbsp;</p>
            <h4><?=$this->T('自动重定向')?></h4>
            <span class='smaller gray'><?=$this->T('P为帖子跳转，按域名后的字符串匹配跳到目标文章；S为站点跳转，可以重定向来源域名，例子：')?>
            <br />P discount:20001001010101;<br />S old_domain:www.new_domain.com:20001001010101;</span>
            <form action="<?=$_SERVER['REQUEST_URI']?>" method="post" style='display:none;' id='settings_form2'></form>
            <textarea id="settings_redirect" name="settings_redirect" rows="4"
                form='settings_form2'><?=$this->DisplayRedirectConfig()?></textarea>
            <input class='button' form="settings_form2" type="submit" name='settings_save_redirect'
                value='<?=$this->T('保存重定向设置')?>' /></input>
            <p>&nbsp;</p>
            <p class='smaller gray'><?=$this->T('当心！下列操作将立即执行：')?></p>
            <ul>
                <li><a href='index.php?rewrite_styles=true'><?=$this->T('重新写入默认CSS')?></a></li>
            </ui>
        </div>
    <?php
    }
    
    function MakeMainBegin(){?>
        <div class='main' ondrop="_dropHandler(event);" ondragover="_dragOverHandler(event);">
    <?php
    }
    function MakeMainEnd(){?>
        </div>
    <?php
    }
    
    function MakeFooter(){?>
        <div class='small_footer'>
            <hr />
            <b><a href='index.php'><?=$this->T($this->Title)?></a></b>
            <span ondblclick='javascript:window.location.href="index.php?settings=true"'>©<?=$this->T($this->DisplayName)?></span>
            <?php if($this->LoggedIn){ ?>
                <a href='index.php?settings=true'><?=$this->T('设置')?></a>
            <?php } ?>
        </div>
        <div class='footer'>
            <div style='white-space:nowrap;'>
                <div class='footer_additional'>
                <?php if(isset($this->SpecialFooter) && ($p = &$this->GetPost($this->SpecialFooter))!=NULL){
                    echo $this->TranslatePostParts($this->GenerateSinglePost($p, false, false, false, false));
                } ?>
                </div>
                <div class='footer_additional'>
                <?php if(isset($this->SpecialFooter2) && ($p = &$this->GetPost($this->SpecialFooter2))!=NULL){
                    echo $this->TranslatePostParts($this->GenerateSinglePost($p, false, false, false, false));
                } ?>
                </div>
            </div>
        </div>
        <p>&nbsp;<p>
        <div id='dropping_background' style='display:none;' onclick='this.style.display="none";'
            ondrop="_dropHandler(event);" ondragover="_dragOverHandler(event);">
            <h2><?=$this->T('上传到这里')?></h2>
        </div>
        <div id='big_image_overlay' style='display:none'>
            <div class='big_image_box' onclick='HideBigImage()'>
                <img id='big_image' />
            </div>
            <div class='big_side_box' onclick='HideBigImage();'>
                <div class='side_box_mobile_inner'>
                    <div style='text-align:right;'>
                        <a class='clean_a' onclick='HideBigImage();'><?=$this->T('关闭')?>→</a>
                        <hr />
                    </div>
                    <div id='big_image_share' class='clean_a' onclick="event.stopPropagation();">
                        <li>
                            <a id='big_share_copy'>📋︎</a>
                            <a id='big_share_pin' target='_blank'>Pin</a>
                            <a id='big_share_twitter' target='_blank'>Twitter</a>
                            <a id='big_share_weibo' target='_blank'><?=$this->T('微博')?></a>
                        </li>
                        <hr />
                    </div>
                    <div id='big_image_info' onclick="event.stopPropagation();"></div>
                </div>
            </div>
        </div>
        
        </div><!-- page -->
        </body>
        </html>
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
            <?php if($this->LoggedIn){ ?>
                function ShowSideUploader(){
                    ShowRightSide(true,null);
                    put = document.querySelector("#_uploader");
                    put.style.display='block';
                    RefreshSideGallery();
                }
                dmark = document.querySelector("#mark_details");
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
                    }
                }
                function CopyRefer(id){
                    copy_text("[<?=$this->T('引用文章')?>]("+id.toString()+")");
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
                            ht = document.querySelector('#post_hint_text');
                            ht.innerHTML = "<?=$this->T('修改帖子：')?>"; ht.style.display='block';
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
                        "index.php?<?=isset($_GET['post'])?('post='.$_GET['post']):""?>&mark_delete="+op+'&target='+id.toString();
                }
                function SetMark(mark){
                    menu = document.getElementById('post_menu');
                    window.location.href=
                        "index.php?<?=isset($_GET['post'])?('post='.$_GET['post']):""?>&target="+
                        menu.parentNode.dataset.postId+"&set_mark="+mark;
                }
                function ToggleMarkDetails(){
                    if(dmark.style.display=='block') dmark.style.display='none';
                    else dmark.style.display='block';
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
                <?php if($this->LoggedIn){ ?>
                    menu.querySelector('#menu_refer').href='javascript:MakeRefer(id)';
                    menu.querySelector('#menu_refer_copy').href='javascript:CopyRefer(id)';
                    ed = menu.querySelector('#menu_edit')
                    ed.href='javascript:MakeEdit(id)'; ed.innerHTML="<?=$this->T('修改')?>";
                    d = menu.querySelector('#menu_delete');
                    d.href='javascript:MarkDelete(\"'+id+'\")';
                    p = document.querySelector('[data-post-id="'+id+'"]');
                    d.innerHTML = p.dataset.markDelete?"<?=$this->T('恢复')?>":"<?=$this->T('删除')?>";
                    menu.querySelector('#mark_details').dataset.id=id;
                <?php } ?>
                
                title = document.title;
                copy = document.getElementById('share_copy');
                copy.innerHTML='&#128203;&#xfe0e;';
                copy.addEventListener("click", function(){
                    copy_text(window.location.href);
                    this.innerHTML='&#128203;&#xfe0e;&#10003;&#xfe0e;';
                });
                document.getElementById('share_pin').href='https://www.pinterest.com/pin/create/button/?url='+
                    encodeURIComponent(window.location.href)+
                    //'&media='+abs_img+
                    '&description='+encodeURIComponent(title);
                document.getElementById('share_twitter').href='http://twitter.com/share?text='+
                    encodeURIComponent(title)+
                    '&url='+window.location.href+
                    "&hashtags="
                document.getElementById('share_weibo').href='https://service.weibo.com/share/share.php?title='+
                    encodeURIComponent(title)+
                    ': '+window.location.href
            }
            function HidePopMenu(){
                var menus = document.querySelectorAll('.pop_menu');
                [].forEach.call(menus, function(m){m.style.display='none';});
            }
            var posts = document.querySelectorAll('.center .post');
            [].forEach.call(posts, function(p){
                p.querySelector('._menu_hook').addEventListener("click", function() {
                    ShowPostMenu(this.parentNode.parentNode);
                });
            });
            var post_clickables = document.querySelectorAll('.center .post a');
            [].forEach.call(post_clickables, function(p){
                p.addEventListener("click", function(event){
                    event.stopPropagation();
                });
            });
            
            pushed=0;
            function ShowBigImage(imgsrc,do_push){
                share = document.querySelector('#big_image_share');
                img = document.querySelector('#big_image');
                img.src = src = "images/"+imgsrc;
                
                if(do_push){PushGalleryHistory(src)}
                
                title = document.title;
                copy = document.getElementById('big_share_copy');
                copy.innerHTML='&#128203;&#xfe0e;';
                copy.addEventListener("click", function(){
                    copy_text(window.location.href);
                    this.innerHTML='&#128203;&#xfe0e;&#10003;&#xfe0e;';
                });
                document.getElementById('big_share_pin').href='https://www.pinterest.com/pin/create/button/?url='+
                    encodeURIComponent(window.location.href)+
                    '&media='+window.location.host+'/'+src+
                    '&description='+encodeURIComponent(title);
                document.getElementById('big_share_twitter').href='http://twitter.com/share?text='+
                    encodeURIComponent(title)+
                    '&url='+window.location.href+
                    "&hashtags="
                document.getElementById('big_share_weibo').href='https://service.weibo.com/share/share.php?title='+
                    encodeURIComponent(title)+
                    ': '+window.location.href
                
                o = document.querySelector('#big_image_overlay');
                info = document.querySelector('#big_image_info');
                info.innerHTML="<?=$this->T('正在查询……')?>";
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
                            content+="<div class='clean_a'>"+res[1]+"</div>";
                        }

                        info.innerHTML=content;
                    }
                };
                xhr.open("GET", "index.php?image_info="+imgsrc+"", true);
                xhr.send();
            }
            function HideBigImage(){
                o = document.querySelector('#big_image_overlay');
                img = document.querySelector('#big_image');
                img.src = "";
                o.style.display="none";
                HideBackdrop();
                PopGalleryHistory()
            }
            var images = document.querySelectorAll('img');
            [].forEach.call(images, function(img){
                imgid = null;
                if(img.classList.contains("no_pop") || (!(imgsrc = img.dataset.imgsrc))) return;
                function wrap(imgsrc){return function(){ShowBigImage(imgsrc, 1);}}
                img.addEventListener("click", wrap(imgsrc));
            });
            function PopGalleryHistory(){
                if(pushed){
                    pushed = 0;
                    history.back();
                }
            }
            function PushGalleryHistory(src){
                abs_img = window.location.protocol+"//"+window.location.host+'/'+src
                title = "照片"
                extra = "?";
                sp = new URLSearchParams(window.location.search)
                if(sp.has('post')){extra+="post="+sp.get('post')}
                if(sp.has('gallery')){extra+="&gallery="+sp.get('gallery')}
                window.history.pushState({}, 'Title', extra+'&pic='+src);
                pushed = 1;
            }
            document.addEventListener('keydown', function(e){
                large = document.getElementById('big_image_overlay')
                if (large.style.display!='block') return;
                if(e.key=='Escape'||e.key=='Esc'||e.keyCode==27){
                    HideBigImage();
                }
            }, true);
            window.addEventListener('popstate', (event) => {
                if(event.state){
                    let sp = new URLSearchParams(event.state)
                    if(searchParams.has('pic')){
                        src = searchParams.get('pic')
                        if(onlyimg = src.match(/[0-9]{14,}.(jpg|png|jpeg|gif)/u)) ShowBigImage(onlyimg[0], 0);
                    }
                }else{
                    HideBigImage();
                }
            });
            
            let searchParams = new URLSearchParams(window.location.search)
            if(searchParams.has('pic')){
                src = searchParams.get('pic')
                if(onlyimg = src.match(/[0-9]{14,}.(jpg|png|jpeg|gif)/u)){
                    ShowBigImage(onlyimg[0], 0);
                }
            }
            function _dropHandler(event){ if (typeof dropHandler === "function") dropHandler(event); }
            function _dragOverHandler(event){ if (typeof dragOverHandler === "function") dragOverHandler(event); }
        </script>
        </body>
    <?php
    }
}

$la = new LA;

$la->DoSiteRedirect();

$la->DoLogin();

$err = $la->ProcessRequest($message, $redirect);

$la->SwitchLanguage();

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

$la->MakeHeader();
$la->MakeMainBegin();


if($la->PageType=='extras'){
    $la->MakeExtraOperations();
}else if($la->PageType=='settings'){
    $la->MakeSettings();
}else if($la->PageType=='gallery'){
    $la->MakeGalleryLeft();
    $la->MakeGallerySection();
}else if($la->PageType=='post'){
    if($p = &$la->GetPost($la->CurrentPostID)){
        $la->MakeLinkedPosts($p);
        $la->MakePostSection($p);
        $la->MakeTOC();
    }else{
        echo "Post not found.";
    }
}else{
    $la->MakeHotPosts();
    $la->MakeRecentPosts();
}

$la->MakeMainEnd();
$la->MakeFooter();


?>

