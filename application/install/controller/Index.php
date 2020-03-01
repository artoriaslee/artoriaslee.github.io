<?php
/**
 * Project: Catfish Blog.
 * Author: A.J <804644245@qq.com>
 * Copyright: http://www.catfish-cms.com All rights reserved.
 * Date: 2016/9/29
 */
namespace app\install\controller;

use think\Controller;
use think\Validate;
use think\Request;
use think\Config;
use think\Db;
use think\Url;
use think\Lang;

class Index extends Controller
{
    private $lang;
    public function _initialize()
    {
        $this->lang = Lang::detect();
        $this->lang = $this->filterLanguages($this->lang);
        Lang::load(APP_PATH . 'install/lang/'.$this->lang.'.php');
    }
    public function index()
    {
        $this->check();
        $right = '<span class="glyphicon glyphicon-ok text-success"></span> ';
        $wrong = '<span class="glyphicon glyphicon-remove text-danger"></span> ';
        $data=array();
        $data['phpversion'] = @ phpversion();
        $data['os']=PHP_OS;
        $err = 0;
        if (version_compare($data['phpversion'], '5.4.0', '>=')) {
            $data['phpversion'] = $right . $data['phpversion'];
        }
        else {
            $data['phpversion'] = $wrong . $data['phpversion'];
            $err++;
        }
        if (class_exists('pdo')) {
            $data['pdo'] = $right . Lang::get('Turned on');
        } else {
            $data['pdo'] = $wrong . Lang::get('Unopened');
            $err++;
        }
        if (extension_loaded('pdo_mysql')) {
            $data['pdo_mysql'] = $right . Lang::get('Turned on');
        } else {
            $data['pdo_mysql'] = $wrong . Lang::get('Unopened');
            $err++;
        }
        if (ini_get('file_uploads')) {
            $data['upload_size'] = $right . ini_get('upload_max_filesize');
        } else {
            $data['upload_size'] = $wrong . Lang::get('Upload is prohibited');
        }
        if (function_exists('curl_init')) {
            $data['curl'] = $right . Lang::get('Turned on');
        } else {
            $data['curl'] = $wrong . Lang::get('Unopened');
            $err++;
        }
        if (function_exists('gd_info')) {
            $data['gd'] = $right . Lang::get('Turned on');
        } else {
            $data['gd'] = $wrong . Lang::get('Unopened');
            $err++;
        }
        if (function_exists('session_start')) {
            $data['session'] = $right . Lang::get('Turned on');
        } else {
            $data['session'] = $wrong . Lang::get('Unopened');
            $err++;
        }
        $lujing = ltrim(str_replace('/index.php','',Url::build('/')),'/');
        $folders = array(
            '',
            'data',
            'data/uploads',
            'application',
            'public/common/extended',
            'runtime',
            'runtime/cache',
            'runtime/log',
            'runtime/temp'
        );
        $new_folders=array();
        foreach($folders as $dir){
            $Testdir = "./".$dir;
            $this->createDir($Testdir);
            if($this->testWrite($Testdir)){
                $new_folders[$lujing.$dir]['w']=true;
            }else{
                $new_folders[$lujing.$dir]['w']=false;
                $err++;
            }
            if(is_readable($Testdir)){
                $new_folders[$lujing.$dir]['r']=true;
            }else{
                $new_folders[$lujing.$dir]['r']=false;
                $err++;
            }
        }
        $data['folders']=$new_folders;
        $this->assign('version',Config::get('version'));
        $this->assign('data',$data);
        $this->assign('error',$err);
        $this->domain();
        $view = $this->fetch();
        return $view;
    }
    private function createDir($path, $mode = 0777)
    {
        if(is_dir($path))
            return true;
        $path = str_replace('\\', '/', $path);
        if(substr($path, -1) != '/')
            $path = $path . '/';
        $temp = explode('/', $path);
        $cur_dir = '';
        $max = count($temp) - 1;
        for($i = 0; $i < $max; $i++)
        {
            $cur_dir .= $temp[$i] . '/';
            if (@is_dir($cur_dir))
                continue;
            @mkdir($cur_dir, 0777, true);
            @chmod($cur_dir, 0777);
        }
        return is_dir($path);
    }
    private function testWrite($d)
    {
        $tfile = "_test.txt";
        $fp = @fopen($d . "/" . $tfile, "w");
        if (!$fp) {
            return false;
        }
        fclose($fp);
        $rs = @unlink($d . "/" . $tfile);
        if ($rs) {
            return true;
        }
        return false;
    }
    public function step2()
    {
        $this->check();
        $this->assign('version',Config::get('version'));
        $this->domain();
        $view = $this->fetch();
        return $view;
    }
    public function step3()
    {
        $this->check();
        $rule = [
            'host' => 'require',
            'port' => 'require|number',
            'user' => 'require',
            'name' => 'require',
            'admin' => 'require',
            'pwd' => 'require|min:8',
            'repwd' => 'require',
            'email' => 'require|email'
        ];
        $msg = [
            'host.require' => Lang::get('The database server must be filled out'),
            'port.require' => Lang::get('The database port must be filled in'),
            'port.number' => Lang::get('The database port must be a number'),
            'user.require' => Lang::get('The database user name must be filled in'),
            'name.require' => Lang::get('The database name must be filled in'),
            'admin.require' => Lang::get('The administrator account must be filled in'),
            'pwd.require' => Lang::get('The administrator password is required'),
            'pwd.min' => Lang::get('The administrator password can not be less than 8 characters'),
            'repwd.require' => Lang::get('Confirm password is required'),
            'email.require' => Lang::get('Email is required'),
            'email.email' => Lang::get('Email format is incorrect')
        ];
        $data = [
            'host' => Request::instance()->post('host'),
            'port' => Request::instance()->post('port'),
            'user' => Request::instance()->post('user'),
            'name' => Request::instance()->post('name'),
            'admin' => Request::instance()->post('admin'),
            'pwd' => Request::instance()->post('pwd'),
            'repwd' => Request::instance()->post('repwd'),
            'email' => Request::instance()->post('email')
        ];
        $validate = new Validate($rule, $msg);
        if(!$validate->check($data))
        {
            $this->error($validate->getError());
        }
        elseif($data['pwd'] !== $data['repwd'])
        {
            $this->error(Lang::get('The "Administrator Password" and "Confirm Password" must be the same'));
        }
        else
        {
            try{
                $dbh=new \PDO('mysql:host='.$data['host'].';port='.$data['port'],$data['user'],Request::instance()->post('password'));
                $dbh->exec('CREATE DATABASE IF NOT EXISTS `' . $data['name'] . '` DEFAULT CHARACTER SET utf8');
            }catch(\Exception $e){
                $this->error(Lang::get('Database information error'));
                return false;
            }
            $this->assign('version',Config::get('version'));
            $domain = $this->domain();
            $sql = file_get_contents(APP_PATH . 'install/data/catfish.sql');
            $sql = str_replace("\r", "\n", $sql);
            $sql = explode(";\n", $sql);
            $default_tablepre = "catfish_";
            $sql = str_replace(" `{$default_tablepre}", " `" . Request::instance()->post('prefix'), $sql);
            $sql = str_replace("http://localhost/", $domain, $sql);
            foreach ($sql as $item) {
                $item = trim($item);
                if(empty($item)) continue;
                preg_match('/CREATE TABLE `([^ ]*)`/', $item, $matches);
                $this->dbExec($item);
            }
            $qu = $this->dbExec('select * from '.Request::instance()->post('prefix').'posts where id=1',true);
            if(empty($qu))
            {
                $this->error(Lang::get('Bad database name'));
            }
            $view = $this->fetch();
            echo $view;
            $create_date=date("Y-m-d H:i:s");
            $ip=get_client_ip(0,true);
            $biaoti = Request::instance()->post('biaoti');
            $biaoti = str_replace('\'','\\\'',$biaoti);
            $this->dbExec("INSERT INTO `" . Request::instance()->post('prefix') . "users`
    (id,user_login,user_pass,user_nicename,user_email,user_url,create_time,user_activation_key,user_status,last_login_ip,last_login_time,user_type) VALUES
    (1, '" . Request::instance()->post('admin') . "', '" . md5(Request::instance()->post('pwd')) . "', '" . Request::instance()->post('admin') . "', '" . Request::instance()->post('email') . "', '', '{$create_date}', '', '1', '{$ip}','{$create_date}', 1)");
            $this->dbExec("INSERT INTO `" . Request::instance()->post('prefix') . "options` (option_id,option_name,option_value) VALUES (1, 'title', '" . $biaoti . "')");
            $subtitle = Lang::get('Another Catfish Blog site');
            $this->dbExec("INSERT INTO `" . Request::instance()->post('prefix') . "options` (option_id,option_name,option_value) VALUES (2, 'subtitle', '" . $subtitle . "')");
            $this->dbExec("INSERT INTO `" . Request::instance()->post('prefix') . "options` (option_id,option_name,option_value) VALUES (3, 'keyword', '')");
            $this->dbExec("INSERT INTO `" . Request::instance()->post('prefix') . "options` (option_id,option_name,option_value) VALUES (4, 'description', '')");
            $this->dbExec("INSERT INTO `" . Request::instance()->post('prefix') . "options` (option_id,option_name,option_value) VALUES (5, 'template', 'cBlog-default')");
            $this->dbExec("INSERT INTO `" . Request::instance()->post('prefix') . "options` (option_id,option_name,option_value) VALUES (6, 'record', '')");
            $copyright = Lang::get('Catfish Blog');
            $this->dbExec("INSERT INTO `" . Request::instance()->post('prefix') . "options` (option_id,option_name,option_value) VALUES (7, 'copyright', '".serialize($copyright)."')");
            $this->dbExec("INSERT INTO `" . Request::instance()->post('prefix') . "options` (option_id,option_name,option_value) VALUES (8, 'statistics', '".serialize('')."')");
            $this->dbExec("INSERT INTO `" . Request::instance()->post('prefix') . "options` (option_id,option_name,option_value,autoload) VALUES (9, 'email', '" . Request::instance()->post('email') . "', 0)");
            $this->dbExec("INSERT INTO `" . Request::instance()->post('prefix') . "options` (option_id,option_name,option_value,autoload) VALUES (10, 'filter', '', 0)");
            $this->dbExec("INSERT INTO `" . Request::instance()->post('prefix') . "options` (option_id,option_name,option_value,autoload) VALUES (11, 'comment', 0, 0)");
            $this->dbExec("INSERT INTO `" . Request::instance()->post('prefix') . "options` (option_id,option_name,option_value,autoload) VALUES (12, 'slideshowWidth', 750, 0)");
            $this->dbExec("INSERT INTO `" . Request::instance()->post('prefix') . "options` (option_id,option_name,option_value,autoload) VALUES (13, 'slideshowHeight', 390, 0)");
            $this->dbExec("INSERT INTO `" . Request::instance()->post('prefix') . "options` (option_id,option_name,option_value,autoload) VALUES (14, 'domain', '".$domain."', 1)");
            $this->dbExec("INSERT INTO `" . Request::instance()->post('prefix') . "options` (option_id,option_name,option_value,autoload) VALUES (15, 'logo', '', 1)");
            $this->dbExec("INSERT INTO `" . Request::instance()->post('prefix') . "options` (option_id,option_name,option_value,autoload) VALUES (16, 'captcha', '1', 0)");
            $this->dbExec("INSERT INTO `" . Request::instance()->post('prefix') . "options` (option_id,option_name,option_value,autoload) VALUES (17, 'bulletin', '', 0)");
            $this->dbExec("INSERT INTO `" . Request::instance()->post('prefix') . "options` (option_id,option_name,option_value,autoload) VALUES (18, 'spare', '', 0)");
            $this->dbExec("INSERT INTO `" . Request::instance()->post('prefix') . "options` (option_id,option_name,option_value,autoload) VALUES (19, 'write', '0', 0)");
            $this->dbExec("INSERT INTO `" . Request::instance()->post('prefix') . "options` (option_id,option_name,option_value,autoload) VALUES (20, 'checkwrite', '0', 0)");
            $useless = 'Q2F0ZmlzaCjpsrbpsbwpIEJsb2fova/ku7bkvb/nlKjljY/orq4KCiAgICDmhJ/osKLmgqjpgInmi6lDYXRmaXNoKOmytumxvCkgQmxvZywg5biM5pyb5oiR5Lus55qE5Lqn5ZOB6IO95aSf5biu5oKo5oqK572R56uZ5Y+R5bGV55qE5pu05b+r44CB5pu05aW944CB5pu05by677yBCgogICAgQ2F0ZmlzaCjpsrbpsbwpIEJsb2flrpjmlrnnvZHnq5nvvJp3d3cuY2F0ZmlzaC1jbXMuY29tCgogICAg5LiA5pem5aSN5Yi244CB5LiL6L2944CB5a6J6KOF5oiW6ICF5Lul5YW25LuW5pa55byP5L2/55So5pys4oCc6L2v5Lu24oCd77yM5Y2z6KGo5piO5oKo5ZCM5oSP5o6l5Y+X5pys5Y2P6K6u5ZCE6aG55p2h5qy+55qE57qm5p2f77yM5ZCM5pe25YyF5ous5o6l5Y+XQ2F0ZmlzaCjpsrbpsbwpIEJsb2fova/ku7blr7nljY/orq7lkITpobnmnaHmrL7pmo/ml7bmiYDlgZrnmoTku7vkvZXkv67mlLnjgILlpoLmnpzmgqjkuI3lkIzmhI/mnKzljY/orq7kuK3nmoTmnaHmrL7vvIzor7fli7/lpI3liLbjgIHkuIvovb3jgIHmn6XnnIvjgIHlronoo4XmiJbogIXku6Xlhbbku5bmlrnlvI/kvb/nlKjmnKzigJzova/ku7bigJ3jgIIKCuiuuOWPr+aCqOeahOadg+WIqeWSjOiMg+WbtO+8mgoKICAgIOaCqOWPr+S7peWcqOWujOWFqOmBteWuiOacrOacgOe7iOeUqOaIt+aOiOadg+WNj+iurueahOWfuuehgOS4iu+8jOWwhkNhdGZpc2go6bK26bG8KSBCbG9n5bqU55So5LqO5Liq5Lq6572R56uZ44CB6Z2e5ZWG5Lia55So6YCU5oiW6ICF5ZWG5Lia55So6YCU55qE572R56uZ77yM6ICM5LiN5b+F5LqL5YWI5pSv5LuY6L2v5Lu254mI5p2D5o6I5p2D6LS555So44CCCiAgICDmgqjlj6/ku6XlnKjljY/orq7op4TlrprnmoTnuqbmnZ/lkozpmZDliLbojIPlm7TlhoXmoLnmja7pnIDopoHlr7lDYXRmaXNoKOmytumxvCkgQmxvZ+eahOS4u+mimOi/m+ihjOW/heimgeeahOS/ruaUueWSjOe+juWMlu+8jOS7pemAguW6lOaCqOeahOe9keermeimgeaxguOAggogICAg5oKo5Y+v5Lul5Zyo5Y2P6K6u6KeE5a6a55qE57qm5p2f5ZKM6ZmQ5Yi26IyD5Zu05YaF5qC55o2u6ZyA6KaB5Yi25L2cQ2F0ZmlzaCjpsrbpsbwpIEJsb2fnmoTmj5Lku7bnlKjmnaXmianlsZXlip/og73vvIzku6XpgILlupTmgqjnmoTnvZHnq5nopoHmsYLjgIIKICAgIOaCqOS4jeiDveS/ruaUuUNhdGZpc2go6bK26bG8KSBCbG9n55qE56iL5bqP5Li75L2T77yM5Lul5Y+K56iL5bqP5Lit5YyF5ZCr55qE5Lu75L2VQ2F0ZmlzaCjpsrbpsbwpIEJsb2fnm7jlhbPniYjmnYPlrZfmoLfjgIIKICAgIOaCqOaLpeacieS9v+eUqENhdGZpc2go6bK26bG8KSBCbG9n5p6E5bu655qE572R56uZ5Lit55qE5YWo6YOo5YaF5a6555qE5omA5pyJ5p2D77yM5bm254us56uL5om/5ouF5LiO5YaF5a6555u45YWz55qE5rOV5b6L5LmJ5Yqh44CCCiAgICDmgqjlj6/ku6XpgJrov4fotK3kubDllYbkuJrmjojmnYPmiJbogIXotK3kubDljZXni6zmnI3liqHnmoTmlrnlvI/ojrflvpfmm7TlpJrnmoTmnI3liqHlkozmlK/mjIHvvIzlkIzml7bkvp3mja7miYDotK3kubDnmoTnsbvlnovkuK3noa7lrprnmoTmioDmnK/mlK/mjIHmnI3liqHnrYnnuqfjgIHmnJ/pmZDjgIHmnI3liqHmlrnlvI/lkozmnI3liqHlhoXlrrnvvIzoh6rotK3kubDml7bliLvotbfvvIzlnKjmioDmnK/mlK/mjIHmnI3liqHmnJ/pmZDlhoXmi6XmnInpgJrov4fmjIflrprnmoTmlrnlvI/ojrflvpfmjIflrprojIPlm7TlhoXnmoTmioDmnK/mlK/mjIHmnI3liqHjgIIKCue6puadn+WSjOmZkOWItu+8mgoKICAgIOacque7j+WumOaWueiuuOWPr++8jOS4jeW+l+WvuUNhdGZpc2go6bK26bG8KSBCbG9n5oiW5LiO5LmL5YWz6IGU55qE5ZWG5Lia5o6I5p2D6L+b6KGM5Ye656ef44CB5Ye65ZSu44CB5oq15oq85oiW5Y+R5pS+5a2Q6K645Y+v6K+B44CCCiAgICDml6DorrrlpoLkvZXvvIzljbPml6DorrrnlKjpgJTlpoLkvZXjgIHmmK/lkKbnu4/ov4fkv67mlLnmiJbnvo7ljJbjgIHkv67mlLnnqIvluqblpoLkvZXvvIzlj6ropoHkvb/nlKhDYXRmaXNoKOmytumxvCkgQmxvZ+eahOaVtOS9k+aIluS7u+S9lemDqOWIhu+8jOacque7j+S5pumdouaOiOadg+iuuOWPr++8jOeoi+W6j+eahOS7u+S9leWcsOaWue+8iOWMheaLrOi9r+S7tumhtemdoumhteiEmuWkhOS7peWPiuS4uumAguW6lOe9keermeimgeaxguiAjOWItuS9nOeahOS4u+mimOWSjOaPkuS7tu+8ieeahENhdGZpc2go6bK26bG8KSBCbG9n54mI5p2DKOeJiOacrCnmoIfor4bjgIHlrZfmoLflkozpk77mjqXpg73lv4Xpobvkv53nlZnvvIzogIzkuI3og73muIXpmaTmiJbkv67mlLnjgIIKICAgIOemgeatouWcqENhdGZpc2go6bK26bG8KSBCbG9n55qE5pW05L2T5oiW5Lu75L2V6YOo5YiG5Z+656GA5LiK5Lul5Y+R5bGV5Lu75L2V5rS+55Sf54mI5pys44CB5L+u5pS554mI5pys5oiW56ys5LiJ5pa554mI5pys55So5LqO6YeN5paw5YiG5Y+R44CCCiAgICDnpoHmraLliKnnlKhDYXRmaXNoKOmytumxvCkgQmxvZ+i9r+S7tuW7uuiuvui/neazleOAgei/neinhO+8jOaIluiAheWPr+mAoOaIkOS4jeiJr+ekvuS8muW9seWTjeetieacieWus+S/oeaBr+eahOe9keermeWPiueUqOmAlOOAggogICAg5aaC5p6c5oKo5pyq6IO96YG15a6I5pys5Y2P6K6u55qE5p2h5qy+77yM5oKo55qE5o6I5p2D5bCG6KKr57uI5q2i77yM5omA6KKr6K645Y+v55qE5p2D5Yip5bCG6KKr5pS25Zue77yM5bm25om/5ouF55u45bqU5rOV5b6L6LSj5Lu744CCCgrlhY3otKPlo7DmmI7vvJoKCiAgICBDYXRmaXNoKOmytumxvCkgQmxvZ+i9r+S7tuS4jeWvueacrOKAnOi9r+S7tuKAneaPkOS+m+S7u+S9leaYjuekuuOAgeaal+ekuuaIluS7u+S9leWFtuWug+W9ouW8j+eahOaLheS/neWSjOihqOekuuOAguWcqOS7u+S9leaDheWGteS4i++8jOWvueS6juWboOS9v+eUqOaIluaXoOazleS9v+eUqOacrOi9r+S7tuiAjOWvvOiHtOeahOS7u+S9leaNn+Wkse+8iOWMheaLrOS9huS4jeS7hemZkOS6juWVhuS4muWIqea2puaNn+WkseOAgeS4muWKoeS4reaWreaIluS4muWKoeS/oeaBr+S4ouWkse+8ie+8jENhdGZpc2go6bK26bG8KSBCbG9n6L2v5Lu25peg6ZyA5ZCR5oKo5oiW5Lu75L2V56ys5LiJ5pa56LSf6LSj77yM5Y2z5L2/Q2F0ZmlzaCjpsrbpsbwpIEJsb2fova/ku7blt7LooqvlkYrnn6Xlj6/og73kvJrpgKDmiJDmraTnsbvmjZ/lpLHjgILlnKjku7vkvZXmg4XlhrXkuIvvvIxDYXRmaXNoKOmytumxvCkgQmxvZ+i9r+S7tuWdh+S4jeWwseS7u+S9leebtOaOpeeahOOAgemXtOaOpeeahOOAgemZhOW4pueahOOAgeWQjuaenOaAp+eahOOAgeeJueWIq+eahOOAgeaDqeaIkuaAp+eahOWSjOWkhOe9muaAp+eahOaNn+Wus+i1lOWBv+aJv+aLheS7u+S9lei0o+S7u++8jOaXoOiuuuivpeS4u+W8oOaYr+WfuuS6juS/neivgeOAgeWQiOWQjOOAgeS+teadg++8iOWMheaLrOeWj+W/ve+8ieaIluaYr+WfuuS6juWFtuS7luWOn+WboOS9nOWHuuOAggogICAgQ2F0ZmlzaCjpsrbpsbwpIEJsb2fova/ku7bkuI3lr7nkvb/nlKjmnKzigJzova/ku7bigJ3mnoTlu7rnmoTnvZHnq5nkuK3ku7vkvZXkv6Hmga/lhoXlrrnku6Xlj4rlr7zoh7TnmoTku7vkvZXniYjmnYPnuqDnurfjgIHms5Xlvovkuonorq7lkozlkI7mnpzmib/mi4Xku7vkvZXotKPku7vvvIzlhajpg6jotKPku7vnlLHmgqjoh6rooYzmib/mi4XjgIIKICAgIENhdGZpc2go6bK26bG8KSBCbG9n6L2v5Lu25Y+v6IO95Lya57uP5bi45o+Q5L6b4oCc6L2v5Lu24oCd5pu05paw5oiW5Y2H57qn77yM5L2GQ2F0ZmlzaCjpsrbpsbwpIEJsb2fova/ku7bmsqHmnInkuLrmoLnmja7mnKzljY/orq7orrjlj6/nmoTigJzova/ku7bigJ3mj5Dkvpvnu7TmiqTmiJbmm7TmlrDnmoTotKPku7vjgIIKCuadg+WIqeWSjOaJgOacieadg+eahOS/neeVmQoKICAgIENhdGZpc2go6bK26bG8KSBCbG9n6L2v5Lu25L+d55WZ5omA5pyJ5pyq5Zyo5pys5Y2P6K6u5Lit5piO56Gu5o6I5LqI5oKo55qE5p2D5Yip44CCQ2F0ZmlzaCjpsrbpsbwpIEJsb2fova/ku7bkv53nlZnpmo/ml7bmm7TmlrDmnKzljY/orq7nmoTmnYPliKnvvIzkuJTml6DpnIDlj6booYzpgJrnn6XvvIzmm7TmlrDlkI7nmoTlhoXlrrnlsIblnKhDYXRmaXNoKOmytumxvCkgQmxvZ+i9r+S7tuWumOaWuee9keermeWFrOW4g++8jOaCqOWPr+S7pemaj+aXtuiuv+mXrkNhdGZpc2go6bK26bG8KSBCbG9n6L2v5Lu25a6Y5pa5572R56uZ5p+l6ZiF5pyA5paw54mI6K645Y+v5p2h5qy+44CCCgogICAg5oKo5LiA5pem5a6J6KOF5L2/55SoQ2F0ZmlzaCjpsrbpsbwpIEJsb2fvvIzljbPooqvop4bkuLrlrozlhajnkIbop6PlubbmjqXlj5fmnKzljY/orq7nmoTlkITpobnmnaHmrL7vvIzlnKjkuqvmnInkuIrov7DmnaHmrL7mjojkuojnmoTmnYPlipvnmoTlkIzml7bvvIzlj5fliLDnm7jlhbPnmoTnuqbmnZ/lkozpmZDliLbjgIIKCi0tLS0tLS0tLS0tLS0tLS0tLS0tLQpDYXRmaXNoKOmvsOmtmikgQmxvZ+i7n+S7tuS9v+eUqOWNlOitsAoKICAgIOaEn+isneaCqOmBuOaTh0NhdGZpc2go6a+w6a2aKSBCbG9nLCDluIzmnJvmiJHlgJHnmoTnlKLlk4Hog73lpKDluavmgqjmiorntrLnq5nnmbzlsZXnmoTmm7Tlv6vjgIHmm7Tlpb3jgIHmm7TlvLfvvIEKCiAgICBDYXRmaXNoKOmvsOmtmikgQmxvZ+WumOaWuee2suerme+8mnd3dy5jYXRmaXNoLWNtcy5jb20KCiAgICDkuIDml6bopIfoo73jgIHkuIvovInjgIHlronoo53miJbogIXku6Xlhbbku5bmlrnlvI/kvb/nlKjmnKzigJzou5/ku7bigJ3vvIzljbPooajmmI7mgqjlkIzmhI/mjqXlj5fmnKzljZTorbDlkITpoIXmop3mrL7nmoTntITmnZ/vvIzlkIzmmYLljIXmi6zmjqXlj5dDYXRmaXNoKOmvsOmtmikgQmxvZ+i7n+S7tuWwjeWNlOitsOWQhOmgheaineasvumaqOaZguaJgOWBmueahOS7u+S9leS/ruaUueOAguWmguaenOaCqOS4jeWQjOaEj+acrOWNlOitsOS4reeahOaineasvu+8jOiri+WLv+ikh+WItuOAgeS4i+i8ieOAgeafpeeci+OAgeWuieijneaIluiAheS7peWFtuS7luaWueW8j+S9v+eUqOacrOKAnOi7n+S7tuKAneOAggoK6Kix5Y+v5oKo55qE5qyK5Yip5ZKM56+E5ZyN77yaCgogICAg5oKo5Y+v5Lul5Zyo5a6M5YWo6YG15a6I5pys5pyA57WC55So5oi25o6I5qyK5Y2U6K2w55qE5Z+656SO5LiK77yM5bCHQ2F0ZmlzaCjpr7DprZopIEJsb2fmh4nnlKjmlrzlgIvkurrntrLnq5njgIHpnZ7llYbmpa3nlKjpgJTmiJbogIXllYbmpa3nlKjpgJTnmoTntrLnq5nvvIzogIzkuI3lv4XkuovlhYjmlK/ku5jou5/ku7bniYjmrIrmjojmrIrosrvnlKjjgIIKICAgIOaCqOWPr+S7peWcqOWNlOitsOimj+WumueahOe0hOadn+WSjOmZkOWItuevhOWcjeWFp+agueaTmumcgOimgeWwjUNhdGZpc2go6a+w6a2aKSBCbG9n55qE5Li76aGM6YCy6KGM5b+F6KaB55qE5L+u5pS55ZKM576O5YyW77yM5Lul6YGp5oeJ5oKo55qE57ay56uZ6KaB5rGC44CCCiAgICDmgqjlj6/ku6XlnKjljZTorbDopo/lrprnmoTntITmnZ/lkozpmZDliLbnr4TlnI3lhafmoLnmk5rpnIDopoHoo73kvZxDYXRmaXNoKOmvsOmtmikgQmxvZ+eahOaPkuS7tueUqOS+huaTtOWxleWKn+iDve+8jOS7pemBqeaHieaCqOeahOe2suermeimgeaxguOAggogICAg5oKo5LiN6IO95L+u5pS5Q2F0ZmlzaCjpr7DprZopIEJsb2fnmoTnqIvluo/kuLvpq5TvvIzku6Xlj4rnqIvluo/kuK3ljIXlkKvnmoTku7vkvZVDYXRmaXNoKOmvsOmtmikgQmxvZ+ebuOmXnOeJiOasiuWtl+aoo+OAggogICAg5oKo5pOB5pyJ5L2/55SoQ2F0ZmlzaCjpr7DprZopIEJsb2fmp4vlu7rnmoTntrLnq5nkuK3nmoTlhajpg6jlhaflrrnnmoTmiYDmnInmrIrvvIzkuKbnjajnq4vmib/mk5ToiIflhaflrrnnm7jpl5znmoTms5Xlvovnvqnli5njgIIKICAgIOaCqOWPr+S7pemAmumBjuizvOiyt+WVhualreaOiOasiuaIluiAheizvOiyt+WWrueNqOacjeWLmeeahOaWueW8j+eNsuW+l+abtOWkmueahOacjeWLmeWSjOaUr+aMge+8jOWQjOaZguS+neaTmuaJgOizvOiyt+eahOmhnuWei+S4reeiuuWumueahOaKgOihk+aUr+aMgeacjeWLmeetiee0muOAgeacn+mZkOOAgeacjeWLmeaWueW8j+WSjOacjeWLmeWFp+Wuue+8jOiHquizvOiyt+aZguWIu+i1t++8jOWcqOaKgOihk+aUr+aMgeacjeWLmeacn+mZkOWFp+aTgeaciemAmumBjuaMh+WumueahOaWueW8j+eNsuW+l+aMh+WumuevhOWcjeWFp+eahOaKgOihk+aUr+aMgeacjeWLmeOAggoK57SE5p2f5ZKM6ZmQ5Yi277yaCgogICAg5pyq57aT5a6Y5pa56Kix5Y+v77yM5LiN5b6X5bCNQ2F0ZmlzaCjpr7DprZopIEJsb2fmiJboiIfkuYvpl5zoga/nmoTllYbmpa3mjojmrIrpgLLooYzlh7rnp5/jgIHlh7rllK7jgIHmirXmirzmiJbnmbzmlL7lrZDoqLHlj6/orYnjgIIKICAgIOeEoeirluWmguS9le+8jOWNs+eEoeirlueUqOmAlOWmguS9leOAgeaYr+WQpue2k+mBjuS/ruaUueaIlue+juWMluOAgeS/ruaUueeoi+W6puWmguS9le+8jOWPquimgeS9v+eUqENhdGZpc2go6a+w6a2aKSBCbG9n55qE5pW06auU5oiW5Lu75L2V6YOo5YiG77yM5pyq57aT5pu46Z2i5o6I5qyK6Kix5Y+v77yM56iL5bqP55qE5Lu75L2V5Zyw5pa577yI5YyF5ous6Luf5Lu26aCB6Z2i6aCB6IWz6JmV5Lul5Y+K54K66YGp5oeJ57ay56uZ6KaB5rGC6ICM6KO95L2c55qE5Li76aGM5ZKM5o+S5Lu277yJ55qEQ2F0ZmlzaCjpr7DprZopIEJsb2fniYjmrIoo54mI5pysKeaomeitmOOAgeWtl+aoo+WSjOmPiOaOpemDveW/hemgiOS/neeVme+8jOiAjOS4jeiDvea4hemZpOaIluS/ruaUueOAggogICAg56aB5q2i5ZyoQ2F0ZmlzaCjpr7DprZopIEJsb2fnmoTmlbTpq5TmiJbku7vkvZXpg6jliIbln7rnpI7kuIrku6XnmbzlsZXku7vkvZXmtL7nlJ/niYjmnKzjgIHkv67mlLnniYjmnKzmiJbnrKzkuInmlrnniYjmnKznlKjmlrzph43mlrDliIbnmbzjgIIKICAgIOemgeatouWIqeeUqENhdGZpc2go6a+w6a2aKSBCbG9n6Luf5Lu25bu66Kit6YGV5rOV44CB6YGV6KaP77yM5oiW6ICF5Y+v6YCg5oiQ5LiN6Imv56S+5pyD5b2x6Z+/562J5pyJ5a6z5L+h5oGv55qE57ay56uZ5Y+K55So6YCU44CCCiAgICDlpoLmnpzmgqjmnKrog73pgbXlrojmnKzljZTorbDnmoTmop3mrL7vvIzmgqjnmoTmjojmrIrlsIfooqvntYLmraLvvIzmiYDooqvoqLHlj6/nmoTmrIrliKnlsIfooqvmlLblm57vvIzkuKbmib/mk5Tnm7jmh4nms5Xlvovosqzku7vjgIIKCuWFjeiyrOiBsuaYju+8mgoKICAgIENhdGZpc2go6a+w6a2aKSBCbG9n6Luf5Lu25LiN5bCN5pys4oCc6Luf5Lu24oCd5o+Q5L6b5Lu75L2V5piO56S644CB5pqX56S65oiW5Lu75L2V5YW25a6D5b2i5byP55qE5pOU5L+d5ZKM6KGo56S644CC5Zyo5Lu75L2V5oOF5rOB5LiL77yM5bCN5pa85Zug5L2/55So5oiW54Sh5rOV5L2/55So5pys6Luf5Lu26ICM5bCO6Ie055qE5Lu75L2V5pCN5aSx77yI5YyF5ous5L2G5LiN5YOF6ZmQ5pa85ZWG5qWt5Yip5r2k5pCN5aSx44CB5qWt5YuZ5Lit5pa35oiW5qWt5YuZ5L+h5oGv5Lif5aSx77yJ77yMQ2F0ZmlzaCjpr7DprZopIEJsb2fou5/ku7bnhKHpnIDlkJHmgqjmiJbku7vkvZXnrKzkuInmlrnosqDosqzvvIzljbPkvb9DYXRmaXNoKOmvsOmtmikgQmxvZ+i7n+S7tuW3suiiq+WRiuefpeWPr+iDveacg+mAoOaIkOatpOmhnuaQjeWkseOAguWcqOS7u+S9leaDheazgeS4i++8jENhdGZpc2go6a+w6a2aKSBCbG9n6Luf5Lu25Z2H5LiN5bCx5Lu75L2V55u05o6l55qE44CB6ZaT5o6l55qE44CB6ZmE5bi255qE44CB5b6M5p6c5oCn55qE44CB54m55Yil55qE44CB5oey5oiS5oCn55qE5ZKM6JmV572w5oCn55qE5pCN5a6z6LOg5YSf5om/5pOU5Lu75L2V6LKs5Lu777yM54Sh6KuW6Kmy5Li75by15piv5Z+65pa85L+d6K2J44CB5ZCI5ZCM44CB5L615qyK77yI5YyF5ous55aP5b+977yJ5oiW5piv5Z+65pa85YW25LuW5Y6f5Zug5L2c5Ye644CCCiAgICBDYXRmaXNoKOmvsOmtmikgQmxvZ+i7n+S7tuS4jeWwjeS9v+eUqOacrOKAnOi7n+S7tuKAneani+W7uueahOe2suermeS4reS7u+S9leS/oeaBr+WFp+WuueS7peWPiuWwjuiHtOeahOS7u+S9leeJiOasiuezvue0m+OAgeazleW+i+eIreitsOWSjOW+jOaenOaJv+aTlOS7u+S9leiyrOS7u++8jOWFqOmDqOiyrOS7u+eUseaCqOiHquihjOaJv+aTlOOAggogICAgQ2F0ZmlzaCjpr7DprZopIEJsb2fou5/ku7blj6/og73mnIPntpPluLjmj5DkvpvigJzou5/ku7bigJ3mm7TmlrDmiJbljYfntJrvvIzkvYZDYXRmaXNoKOmvsOmtmikgQmxvZ+i7n+S7tuaykuacieeCuuagueaTmuacrOWNlOitsOioseWPr+eahOKAnOi7n+S7tuKAneaPkOS+m+e2reitt+aIluabtOaWsOeahOiyrOS7u+OAggoK5qyK5Yip5ZKM5omA5pyJ5qyK55qE5L+d55WZCgogICAgQ2F0ZmlzaCjpr7DprZopIEJsb2fou5/ku7bkv53nlZnmiYDmnInmnKrlnKjmnKzljZTorbDkuK3mmI7norrmjojkuojmgqjnmoTmrIrliKnjgIIgQ2F0ZmlzaCjpr7DprZopIEJsb2fou5/ku7bkv53nlZnpmqjmmYLmm7TmlrDmnKzljZTorbDnmoTmrIrliKnvvIzkuJTnhKHpnIDlj6booYzpgJrnn6XvvIzmm7TmlrDlvoznmoTlhaflrrnlsIflnKhDYXRmaXNoKOmvsOmtmikgQmxvZ+i7n+S7tuWumOaWuee2suermeWFrOS9iO+8jOaCqOWPr+S7pemaqOaZguioquWVj0NhdGZpc2go6a+w6a2aKSBCbG9n6Luf5Lu25a6Y5pa557ay56uZ5p+l6Zax5pyA5paw54mI6Kix5Y+v5qKd5qy+44CCCgogICAg5oKo5LiA5pem5a6J6KOd5L2/55SoQ2F0ZmlzaCjpr7DprZopIEJsb2fvvIzljbPooqvoppbngrrlrozlhajnkIbop6PkuKbmjqXlj5fmnKzljZTorbDnmoTlkITpoIXmop3mrL7vvIzlnKjkuqvmnInkuIrov7Dmop3mrL7mjojkuojnmoTmrIrlipvnmoTlkIzmmYLvvIzlj5fliLDnm7jpl5znmoTntITmnZ/lkozpmZDliLbjgIIKCi0tLS0tLS0tLS0tLS0tLS0tLS0tLQpDYXRmaXNoIEJsb2cgc29mdHdhcmUgdXNhZ2UgYWdyZWVtZW50CgogICAgVGhhbmsgeW91IGZvciBjaG9vc2luZyBDYXRmaXNoIEJsb2csIGhvcGUgdGhhdCBvdXIgcHJvZHVjdHMgY2FuIGhlbHAgeW91IHRvIGRldmVsb3AgeW91ciB3ZWJzaXRlIGZhc3RlciwgYmV0dGVyIGFuZCBzdHJvbmdlciEKCiAgICBDYXRmaXNoIEJsb2cgb2ZmaWNpYWwgd2Vic2l0ZTogd3d3LmNhdGZpc2gtY21zLmNvbQoKICAgIEJ5IGNvcHlpbmcsIGRvd25sb2FkaW5nLCBpbnN0YWxsaW5nLCBvciBvdGhlcndpc2UgdXNpbmcgdGhpcyBTT0ZUV0FSRSwgWU9VIEFHUkVFIFRPIEJFIEJPVU5EIEJZIFRIRSBURVJNUyBPRiBUSElTIEFHUkVFTUVOVCwgQU5EIElORElDQVRFIFlPVVIgQUNDRVBUQU5DRSBPRiBBTlkgQ0hBTkdFUyBXSElDSCBNQVkgQkUgUE9TU0lCTEUgVU5ERVIgQU5ZIFRISVMgQUdSRUVNRU5ULiBJRiBZT1UgRE8gTk9UIEFHUkVFIFRPIFRIRSBURVJNUyBPRiBUSElTIEFHUkVFTUVOVCwgRE8gTk9UIENPUFksIERPV05MT0FELCBWSUVXLCBJTlNUQUxMIE9SIE9USEVSV0lTRSBVU0UgVEhFIFNPRlRXQVJFLgoKUGVybWl0IHlvdXIgcmlnaHRzIGFuZCBzY29wZToKCiAgICBZb3UgY2FuIGFwcGx5IENhdGZpc2ggQmxvZyB0byBhIHdlYiBzaXRlLCBhIG5vbiBjb21tZXJjaWFsIG9yIGEgY29tbWVyY2lhbCB3ZWIgc2l0ZSBvbiB0aGUgYmFzaXMgb2YgZnVsbCBjb21wbGlhbmNlIHdpdGggdGhlIGVuZC11c2VyIGF1dGhvcml6YXRpb24gYWdyZWVtZW50LCB3aXRob3V0IGhhdmluZyB0byBwYXkgdGhlIHNvZnR3YXJlIGNvcHlyaWdodCBsaWNlbnNpbmcgZmVlcyBpbiBhZHZhbmNlLgogICAgV2l0aGluIHRoZSB0ZXJtcyBvZiB0aGUgYWdyZWVtZW50LCB5b3UgbWF5IG1ha2UgbmVjZXNzYXJ5IG1vZGlmaWNhdGlvbnMgYW5kIGVuaGFuY2VtZW50cyB0byB0aGUgQ2F0ZmlzaCBCbG9nIHRoZW1lcyBhcyBuZWVkZWQgdG8gc3VpdCB5b3VyIHNpdGUgcmVxdWlyZW1lbnRzLgogICAgWW91IGNhbiBtYWtlIENhdGZpc2ggQmxvZyBwbHVnLWlucyBhcyBuZWVkZWQgd2l0aGluIHRoZSBjb25zdHJhaW50cyBvZiB0aGUgYWdyZWVtZW50IHRvIGV4dGVuZCBmdW5jdGlvbmFsaXR5IHRvIHN1aXQgeW91ciB3ZWJzaXRlIHJlcXVpcmVtZW50cy4KICAgIFRoZSBwcm9ncmFtcyB0aGF0IG1vZGlmeSB0aGUgQ2F0ZmlzaCBCbG9nIHNvZnR3YXJlIGFyZSBwcm9oaWJpdGVkLCBhbmQgYW55IGNvcHlyaWdodGVkIGluZm9ybWF0aW9uIGFib3V0IHRoZSBDYXRmaXNoIEJsb2cgY29udGFpbmVkIGluIHRoZSBwcm9ncmFtLgogICAgWW91IG93biB0aGUgZW50aXJlIGNvbnRlbnRzIG9mIGEgd2Vic2l0ZSBidWlsdCB3aXRoIHRoZSBDYXRmaXNoIEJsb2cgYW5kIGFzc3VtZSB0aGUgbGVnYWwgb2JsaWdhdGlvbnMgcmVsYXRlZCB0byB0aGUgY29udGVudC4KICAgIFlvdSBjYW4gZ2V0IG1vcmUgc2VydmljZXMgYW5kIHN1cHBvcnQgYnkgcHVyY2hhc2luZyBhIGNvbW1lcmNpYWwgbGljZW5zZSBvciBwdXJjaGFzaW5nIGEgc2VwYXJhdGUgc2VydmljZSwgZGVwZW5kaW5nIG9uIHRoZSBsaWNlbnNlIHlvdSBwdXJjaGFzZWQgb3IgdGhlIHJlbGV2YW50IGNvbnRlbnQgZGV0ZXJtaW5lZCBpbiB0aGUgc2VydmljZSAodGVjaG5pY2FsIHN1cHBvcnQsIHNlcnZpY2UgbGV2ZWwsIGRlYWRsaW5lLCBzZXJ2aWNlIG1ldGhvZCBhbmQgc2VydmljZSBjb250ZW50KS4gQWZ0ZXIgdGhlIHB1cmNoYXNlLCB0aGUgdGVjaG5pY2FsIHN1cHBvcnQgc2VydmljZSB3aXRoaW4gdGhlIHNwZWNpZmllZCBzY29wZSBpcyBvYnRhaW5lZCB3aXRoaW4gdGhlIHNwZWNpZmllZCBwZXJpb2Qgb2YgdGhlIHRlY2huaWNhbCBzdXBwb3J0IHNlcnZpY2UuCgpDb25zdHJhaW50cyBhbmQgcmVzdHJpY3Rpb25zOgoKICAgIENhdGZpc2ggQmxvZyBpcyBmb3JiaWRkZW4gdG8gcmVudCwgc2VsbCwgbW9ydGdhZ2Ugb3Igc3ViLWxpY2Vuc2UgdGhlIENhdGZpc2ggQmxvZyBhbmQgYXNzb2NpYXRlZCBjb21tZXJjaWFsIGxpY2Vuc2VzIHdpdGhvdXQgdGhlIG9mZmljaWFsIHBlcm1pc3Npb24gb2YgQ2F0ZmlzaCBCbG9nLgogICAgSW4gYW55IGV2ZW50LCB0aGF0IGlzIHRvIHNheSwgcmVnYXJkbGVzcyBvZiB1c2FnZSwgbW9kaWZpY2F0aW9uIG9yIGJlYXV0aWZpY2F0aW9uLCBob3cgd2VsbCBpdCBpcyBtb2RpZmllZCwgc2ltcGx5IGJ5IHVzaW5nIHRoZSBlbnRpcmUgb3IgYW55IHBhcnQgb2YgdGhlIENhdGZpc2ggQmxvZyB3aXRob3V0IHRoZSB3cml0dGVuIHBlcm1pc3Npb24gb2YgdGhlIGF1dGhvciwgdGhlIENhdGZpc2ggQmxvZyBDb3B5cmlnaHQgKFZlcnNpb24pIElkZW50aWZ5IEFueXdoZXJlIGluIHRoZSBQcm9ncmFtICwgV29yZHMgYW5kIGxpbmtzIG11c3QgYmUgcmV0YWluZWQsIGFuZCBjYW4gbm90IGJlIGNsZWFyZWQgb3IgbW9kaWZpZWQuCiAgICBJdCBpcyBmb3JiaWRkZW4gdG8gZGV2ZWxvcCBhbnkgZGVyaXZhdGl2ZSwgbW9kaWZpZWQgb3IgdGhpcmQtcGFydHkgdmVyc2lvbiBmb3IgcmVkaXN0cmlidXRpb24gb24gdGhlIGJhc2lzIG9mIHRoZSB3aG9sZSBvciBhbnkgcGFydCBvZiBDYXRmaXNoIEJsb2cuCiAgICBJdCBpcyBmb3JiaWRkZW4gdG8gdXNlIENhdGZpc2ggQmxvZyBzb2Z0d2FyZSB0byBidWlsZCBzaXRlcyB0aGF0IHZpb2xhdGUgdGhlIGxhdyBvciBjYXVzZSBhZHZlcnNlIHNvY2lhbCBlZmZlY3RzLgogICAgSWYgeW91IGZhaWwgdG8gY29tcGx5IHdpdGggdGhlIHRlcm1zIG9mIHRoaXMgYWdyZWVtZW50LCB5b3VyIGF1dGhvcml6YXRpb24gd2lsbCBiZSB0ZXJtaW5hdGVkIGFuZCB0aGUgcmlnaHRzIGdyYW50ZWQgd2lsbCBiZSByZWNvdmVyYWJsZSBhbmQgYmVhciB0aGUgY29ycmVzcG9uZGluZyBsZWdhbCBsaWFiaWxpdGllcy4KCkRpc2NsYWltZXI6CgogICAgQ2F0ZmlzaCBCbG9nIHNvZnR3YXJlIGRvZXMgbm90IHByb3ZpZGUgYW55IHdhcnJhbnR5LCBleHByZXNzIG9yIGltcGxpZWQsIG9yIGFueSBvdGhlciBmb3JtIG9mIHJlcHJlc2VudGF0aW9uLiBJTiBOTyBFVkVOVCBTSEFMTCBDYXRmaXNoIEJsb2cgU29mdHdhcmUgQkUgTElBQkxFIFRPIFlPVSBPUiBBTlkgVEhJUkQgUEFSVFkgRk9SIEFOWSBMT1NTIENBVVNFRCBCWSBVU0UgT1IgSU5BQklMSVRZIFRPIFVTRSBUSEUgU09GVFdBUkUsIElOQ0xVRElORywgQlVUIE5PVCBMSU1JVEVEIFRPLCBEQU1BR0VTIEZPUiBMT1NTIE9GIEJVU0lORVNTIFBST0ZJVFMgT1IgQlVTSU5FU1MgSU5URVJSVVBUSU9OIE9SIEJVU0lORVNTIExPU1MsIEVWRU4gSUYgQ2F0ZmlzaCBCbG9nIHNvZnR3YXJlIGhhcyBiZWVuIGFkdmlzZWQgb2YgdGhlIHBvdGVudGlhbCBmb3Igc3VjaCBkYW1hZ2VzLiBVbmRlciBubyBjaXJjdW1zdGFuY2VzIHdpbGwgQ2F0ZmlzaCBCbG9nIFNvZnR3YXJlIGJlIGxpYWJsZSBmb3IgYW55IGRpcmVjdCwgaW5kaXJlY3QsIGluY2lkZW50YWwsIGNvbnNlcXVlbnRpYWwsIHNwZWNpYWwsIGV4ZW1wbGFyeSBhbmQgcHVuaXNoYWJsZSBkYW1hZ2VzLCB3aGV0aGVyIGJhc2VkIG9uIHdhcnJhbnR5LCBjb250cmFjdCwgSW5mcmluZ2VtZW50IChpbmNsdWRpbmcgbmVnbGlnZW5jZSkgb3IgZm9yIG90aGVyIHJlYXNvbnMuCiAgICBDYXRmaXNoIEJsb2cgc29mdHdhcmUgYXNzdW1lcyBubyByZXNwb25zaWJpbGl0eSB3aGF0c29ldmVyIGZvciBhbnkgY29udGVudCBjb250YWluZWQgaW4gYW55IHdlYnNpdGUgY29uc3RydWN0ZWQgd2l0aCB0aGlzIHNvZnR3YXJlIGFuZCBhbnkgcmVzdWx0aW5nIGNvcHlyaWdodCBkaXNwdXRlcywgbGVnYWwgZGlzcHV0ZXMgYW5kIGNvbnNlcXVlbmNlcy4gQWxsIHJlc3BvbnNpYmlsaXR5IGlzIHlvdXIgb3duIHJlc3BvbnNpYmlsaXR5LgogICAgQ2F0ZmlzaCBCbG9nIHNvZnR3YXJlIG1heSBmcmVxdWVudGx5IHByb3ZpZGUgInNvZnR3YXJlIiB1cGRhdGVzIG9yIHVwZ3JhZGVzLCBidXQgQ2F0ZmlzaCBCbG9nIHNvZnR3YXJlIGRvZXMgbm90IGhhdmUgdG8gYmUgcmVzcG9uc2libGUgZm9yIG1haW50YWluaW5nIG9yIHVwZGF0aW5nIHRoZSBzb2Z0d2FyZS4KClJldGVudGlvbiBvZiByaWdodHMgYW5kIG93bmVyc2hpcAoKICAgIENhdGZpc2ggQmxvZyBzb2Z0d2FyZSByZXNlcnZlcyBhbGwgcmlnaHRzIG5vdCBleHByZXNzbHkgZ3JhbnRlZCB0byB5b3UgaW4gdGhpcyBhZ3JlZW1lbnQuIENhdGZpc2ggQmxvZyBTb2Z0d2FyZSByZXNlcnZlcyB0aGUgcmlnaHQgdG8gdXBkYXRlIHRoaXMgQWdyZWVtZW50IGF0IGFueSB0aW1lIHdpdGhvdXQgbm90aWNlIGFuZCB0aGUgdXBkYXRlZCBjb250ZW50IHdpbGwgYmUgcG9zdGVkIG9uIHRoZSBvZmZpY2lhbCBDYXRmaXNoIEJsb2cgU29mdHdhcmUgd2Vic2l0ZSB3aGVyZSB5b3UgbWF5IHZpc2l0IHRoZSBvZmZpY2lhbCBDYXRmaXNoIEJsb2cgU29mdHdhcmUgd2Vic2l0ZSBmb3IgdGhlIGxhdGVzdCBsaWNlbnNlIHRlcm1zLgoKICAgIE9uY2UgeW91IGluc3RhbGwgQ2F0ZmlzaCBCbG9nLCB5b3UgYXJlIGRlZW1lZCBmdWxseSB1bmRlcnN0YW5kIGFuZCBhY2NlcHQgdGhlIHRlcm1zIG9mIHRoaXMgYWdyZWVtZW50LCB5b3UgZW5qb3kgdGhlIHBvd2VyIG9mIHRoZSBhYm92ZSB0ZXJtcywgYnV0IGFsc28gYnkgdGhlIHJlbGV2YW50IHByb3Zpc2lvbnMgb2YgdGhlIGNvbnN0cmFpbnRzIGFuZCByZXN0cmljdGlvbnMu';
            $this->dbExec("INSERT INTO `" . Request::instance()->post('prefix') . "options` (option_id,option_name,option_value,autoload) VALUES (21, 'useless', '".$useless."', 0)");
            $conf = file_get_contents(APP_PATH . 'install/data/database.php');
            $data['password'] = Request::instance()->post('password');
            $data['prefix'] = Request::instance()->post('prefix');
            foreach ($data as $key => $value) {
                $conf = str_replace("#{$key}#", $value, $conf);
            }
            file_put_contents(APP_PATH . 'database.php', $conf);
            touch(APP_PATH . 'install.lock');
            echo '<div class="hidden">';
            $this->success(Lang::get('Installation completed'), 'step4');
            echo '</div>';
        }
    }
    public function step4()
    {
        $this->assign('version',Config::get('version'));
        $this->domain();
        $view = $this->fetch();
        return $view;
    }
    private function domain()
    {
        $http = Request::instance()->isSsl() ? 'https://' : 'http://';
        $domain = $http . str_replace("\\",'/',$_SERVER['HTTP_HOST'].str_replace('/index.php','',Url::build('/')));
        $domain = substr($domain, -1, 1) == '/' ? $domain : $domain . '/';
        $this->assign('domain',$domain);
        return $domain;
    }
    private function check()
    {
        if(is_file(APP_PATH . 'install.lock')){
            $this->redirect(Url::build('/index'));
            exit;
        }
    }
    private function dbExec($exStr,$query = false)
    {
        try{
            $cnn = Db::connect([
                // 数据库类型
                'type' => 'mysql',
                // 数据库连接DSN配置
                'dsn' => '',
                // 服务器地址
                'hostname' => Request::instance()->post('host'),
                // 数据库名
                'database' => Request::instance()->post('name'),
                // 数据库用户名
                'username' => Request::instance()->post('user'),
                // 数据库密码
                'password' => Request::instance()->post('password'),
                // 数据库连接端口
                'hostport' => Request::instance()->post('port'),
                // 数据库连接参数
                'params' => [],
                // 数据库编码默认采用utf8
                'charset' => 'utf8',
                // 数据库表前缀
                'prefix' => Request::instance()->post('prefix')
            ]);
            if($query == false)
            {
                $cnn->execute($exStr);
            }
            else
            {
                return $cnn->query($exStr);
            }
        }catch(\Exception $e){
            //echo $e->getMessage();
            return false;
        }
        return true;
    }
    private function filterLanguages($parameter)
    {
        $param = strtolower($parameter);
        if($param == 'zh' || strpos($param,'zh-hans') !== false || strpos($param,'zh-chs') !== false)
        {
            Lang::range('zh-cn');
            return 'zh-cn';
        }
        else if($param == 'zh-tw' || strpos($param,'zh-hant') !== false || strpos($param,'zh-cht') !== false){
            Lang::range('zh-tw');
            return 'zh-tw';
        }
        else if(stripos($param,'zh') === false)
        {
            $paramsub = substr($param,0,2);
            switch($paramsub)
            {
                case 'de':
                    Lang::range('de-de');
                    return 'de-de';
                    break;
                case 'fr':
                    Lang::range('fr-fr');
                    return 'fr-fr';
                    break;
                case 'ja':
                    Lang::range('ja-jp');
                    return 'ja-jp';
                    break;
                case 'ko':
                    Lang::range('ko-kr');
                    return 'ko-kr';
                    break;
                case 'ru':
                    Lang::range('ru-ru');
                    return 'ru-ru';
                    break;
                default:
                    return $param;
            }
        }
        else
        {
            return $param;
        }
    }
}