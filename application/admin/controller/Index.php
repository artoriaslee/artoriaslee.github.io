<?php
/**
 * Project: Catfish Blog.
 * Author: A.J <804644245@qq.com>
 * Copyright: http://www.catfish-cms.com All rights reserved.
 * Date: 2016/10/1
 */
namespace app\admin\controller;

use think\Db;
use think\Request;
use think\Validate;
use think\Session;
use think\Config;
use think\Debug;
use think\Cache;
use think\Hook;
use think\Url;
use think\Lang;

class Index extends Common
{
    public function addclassify()
    {
        $this->checkUser();
        $this->checkPermissions(5);
        if(Request::instance()->has('fenleim','post'))
        {
            $rule = [
                'fenleim' => 'require',
                'shangji' => 'require'
            ];
            $msg = [
                'fenleim.require' => Lang::get('The category name must be filled in'),
                'shangji.require' => Lang::get('The superior category must be selected')
            ];
            $data = [
                'fenleim' => Request::instance()->post('fenleim'),
                'shangji' => Request::instance()->post('shangji')
            ];
            $validate = new Validate($rule, $msg);
            if(!$validate->check($data))
            {
                $this->error($validate->getError());
                return false;
            }
            $data = ['term_name' => htmlspecialchars(Request::instance()->post('fenleim')), 'description' => htmlspecialchars(Request::instance()->post('miaoshu')), 'parent_id' => Request::instance()->post('shangji')];
            Db::name('terms')->insert($data);
        }
        $fufenlei = 0;
        if(Request::instance()->has('c','get'))
        {
            $fufenlei = Request::instance()->get('c');
        }
        $this->assign('fufenlei', $fufenlei);
        $this->assign('backstageMenu', 'neirong');
        $this->assign('option', 'addclassify');
        $this->assign('fenlei', $this->getfenlei());
        return $this->view();
    }
    public function classify()
    {
        $this->checkUser();
        $this->checkPermissions(5);
        if(Request::instance()->has('d','get'))
        {
            Db::name('terms')->where('id',Request::instance()->get('d'))->delete();
            Db::name('terms')
                ->where('parent_id', Request::instance()->get('d'))
                ->update(['parent_id' => Request::instance()->get('f')]);
            Db::name('term_relationships')->where('term_id',Request::instance()->get('d'))->delete();
        }
        $this->assign('data', $this->getfenlei('id,term_name,description,parent_id','&#12288;'));
        $this->assign('backstageMenu', 'neirong');
        $this->assign('option', 'classify');
        return $this->view();
    }
    public function modifyclassify()
    {
        $this->checkUser();
        $this->checkPermissions(5);
        if(Request::instance()->has('cid','post'))
        {
            $rule = [
                'fenleim' => 'require',
                'shangji' => 'require'
            ];
            $msg = [
                'fenleim.require' => Lang::get('The category name must be filled in'),
                'shangji.require' => Lang::get('The superior category must be selected')
            ];
            $data = [
                'fenleim' => Request::instance()->post('fenleim'),
                'shangji' => Request::instance()->post('shangji')
            ];
            $validate = new Validate($rule, $msg);
            if(!$validate->check($data))
            {
                $this->error($validate->getError());
                return false;
            }
            $data = ['term_name' => htmlspecialchars(Request::instance()->post('fenleim')), 'description' => htmlspecialchars(Request::instance()->post('miaoshu')), 'parent_id' => Request::instance()->post('shangji')];
            Db::name('terms')
                ->where('id', Request::instance()->post('cid'))
                ->update($data);
        }
        $data = Db::name('terms')->where('id',Request::instance()->get('c'))->find();
        $this->assign('data', $data);
        $this->assign('backstageMenu', 'neirong');
        $this->assign('option', 'classify');
        $this->assign('fenlei', $this->getfenlei());
        return $this->view();
    }
    public function versioninfo()
    {
        $this->checkUser();
        $data = Db::name('options')->where('option_name','useless')->field('option_value')->find();
        $this->assign('banq', base64_decode($data['option_value']));
        $this->assign('backstageMenu', 'xitong');
        $this->assign('option', 'versioninfo');
        return $this->view();
    }
    public function index()
    {
        $this->checkUser();
        $domain = Cache::get('domain');
        if($domain == false)
        {
            $domain = Db::name('options')->where('option_name','domain')->field('option_value')->find();
            $domain = $domain['option_value'];
            Cache::set('domain',$domain,3600);
        }
        if(!$this->isamedm($domain))
        {
            $this->assign('root', $this->root());
            return $this->view('nosame');
            exit();
        }
        if(Session::get($this->session_prefix.'user_type') < 7)
        {
            $this->redirect(Url::build('admin/Index/write'));
        }
        else
        {
            $this->redirect(Url::build('admin/Index/personal'));
        }
        return false;
    }
    public function write()
    {
        $this->checkUser();
        $this->checkPermissions(6);
        if(Request::instance()->has('biaoti','post'))
        {
            $rule = [
                'biaoti' => 'require',
                'neirong' => 'require'
            ];
            $msg = [
                'biaoti.require' => Lang::get('The title must be filled in'),
                'neirong.require' => Lang::get('Article content must be filled out')
            ];
            $data = [
                'biaoti' => Request::instance()->post('biaoti'),
                'neirong' => Request::instance()->post('neirong')
            ];
            $validate = new Validate($rule, $msg);
            if(!$validate->check($data))
            {
                $this->error($validate->getError());
                return false;
            }
            $biaoti = Request::instance()->post('biaoti');
            $neirong = str_replace('<img','<img class="img-responsive"',$this->filterJs(Request::instance()->post('neirong')));
            $zhaiyao = Request::instance()->post('zhaiyao');
            $guanjianci = str_replace('，',',',Request::instance()->post('guanjianci'));
            Hook::add('write',$this->plugins);
            $params = [
                'title' => $biaoti,
                'content' => $neirong,
                'summary' => ltrim($zhaiyao)
            ];
            Hook::listen('write',$params,$this->ccc);
            $biaoti = $params['title'];
            $neirong = $params['content'];
            $zhaiyao = $params['summary'];
            $fabushijian = date('Y-m-d H:i:s');
            if(Request::instance()->has('fabushijian','post'))
            {
                $fabushijian = Request::instance()->post('fabushijian');
            }
            $zhiding = 0;
            if(Request::instance()->has('zhiding','post'))
            {
                $zhiding = Request::instance()->post('zhiding');
            }
            $tuijian = 0;
            if(Request::instance()->has('tuijian','post'))
            {
                $tuijian = Request::instance()->post('tuijian');
            }
            $pinglun = 1;
            if(Request::instance()->has('pinglun','post'))
            {
                $pinglun = Request::instance()->post('pinglun');
            }
            $shenhe = 1;
            $suolvetu = Request::instance()->post('suolvetu');
            if(!$this->isLegalPicture($suolvetu, false))
            {
                $suolvetu = '';
            }
            if(Request::instance()->has('simiwenzhang','post') && Request::instance()->post('simiwenzhang') == 'on')
            {
                $shenhe = 2;
            }
            $data = ['post_author' => Session::get($this->session_prefix.'user_id'), 'post_keywords' => htmlspecialchars($guanjianci), 'post_source' => htmlspecialchars(Request::instance()->post('laiyuan')), 'post_date' => $fabushijian, 'post_content' => $neirong, 'post_title' => htmlspecialchars($biaoti), 'post_excerpt' => htmlspecialchars($zhaiyao), 'post_status' => $shenhe, 'comment_status' => $pinglun, 'post_modified' => $fabushijian, 'post_type' => Request::instance()->post('xingshi'), 'thumbnail' => $suolvetu, 'istop' => $zhiding, 'recommended' => $tuijian];
            $id = Db::name('posts')->insertGetId($data);
            if(isset($_POST['fenlei']) && is_array($_POST['fenlei']))
            {
                $data = [];
                foreach($_POST['fenlei'] as $key => $val)
                {
                    $data[] = ['object_id' => $id, 'term_id' => $val];
                }
                Db::name('term_relationships')->insertAll($data);
            }
            Hook::add('write_post',$this->plugins);
            $params = [
                'id' => $id
            ];
            Hook::listen('write_post',$params,$this->ccc);
            Hook::add('write_post_later',$this->plugins);
            Hook::listen('write_post_later',$params,$this->ccc);
            Cache::clear('catfishBlog_1');
        }
        $editor = 'HandyEditor';
        if(Request::instance()->has('editor','get'))
        {
            $editor = Request::instance()->get('editor');
            $this->setb('whichEditorToUse',$editor);
        }
        else
        {
            $whichEditorToUse = $this->getb('whichEditorToUse');
            if(!empty($whichEditorToUse))
            {
                $editor = $whichEditorToUse;
            }
        }
        $this->assign('whichEditor', strtolower($editor));
        $this->writeAlias(0);
        $this->writeAppend(0);
        $this->writeAfterEditor(0);
        $this->writeRightAppend(0);
        $this->switchEditor();
        $newk = 1;
        $newv = 1;
        $kongzhi = $this->getb('newv');
        if(!empty($kongzhi))
        {
            $kongzhi = unserialize($kongzhi);
            $version = Config::get('version');
            $version = trim(substr($version['number'],1));
            if(version_compare($version,$kongzhi['version']) < 0)
            {
                $newv = 0;
                $newk = 0;
            }
            $time = time();
            if($time - $kongzhi['time'] < 0)
            {
                $newv = 0;
            }
            if($time - $kongzhi['show'] < 0)
            {
                $newk = 1;
            }
        }
        if($this->permissions > 3)
        {
            $newk = 1;
            $newv = 0;
        }
        $this->assign('newk', $newk);
        $this->assign('newv', $newv);
        $this->assign('backstageMenu', 'neirong');
        $this->assign('option', 'write');
        $this->assign('fenlei', $this->getfenlei());
        return $this->view();
    }
    public function articles()
    {
        $this->checkUser();
        $this->checkPermissions(6);
        if($this->permissions < 6)
        {
            $data = Db::view('posts','id,post_date,post_title,post_status,comment_count,thumbnail,post_hits,istop,recommended')
                ->view('users','user_login','users.id=posts.post_author')
                ->where('post_type',['=',0],['=',2],['=',3],['=',4],['=',5],['=',6],['=',7],['=',8],'or')
                ->where('status','=',1)
                ->where('post_status','<>',2)
                ->order('post_date desc')
                ->paginate(10);
            $this->assign('authority', 'all');
        }
        else
        {
            $data = Db::view('posts','id,post_date,post_title,post_status,comment_count,thumbnail,post_hits,istop,recommended')
                ->view('users','user_login','users.id=posts.post_author')
                ->where('post_author','=',Session::get($this->session_prefix.'user_id'))
                ->where('post_type',['=',0],['=',2],['=',3],['=',4],['=',5],['=',6],['=',7],['=',8],'or')
                ->where('status','=',1)
                ->where('post_status','<>',2)
                ->order('post_date desc')
                ->paginate(10);
            $this->assign('authority', 'part');
        }
        $this->assign('data', $data);
        $this->assign('backstageMenu', 'neirong');
        $this->assign('option', 'articles');
        $this->assign('fenlei', $this->getfenlei());
        $this->assign('toutiao', intval($this->getb('toutiao')));
        return $this->view();
    }
    public function comments()
    {
        $this->checkUser();
        $this->checkPermissions(5);
        $data = Db::name('comments')
            ->alias('c')
            ->order('c.createtime desc')
            ->join('users u','c.uid = u.id','LEFT')
            ->field('c.id,c.post_id,c.createtime,c.content,c.status,u.user_login,u.user_nicename,u.user_email,u.avatar')
            ->paginate(10);
        $this->assign('data', $data);
        $this->assign('backstageMenu', 'neirong');
        $this->assign('option', 'comments');
        return $this->view();
    }
    public function shenhepinglun()
    {
        $this->checkPermissions(5);
        $zt = Request::instance()->post('zt');
        if($zt == 1)
        {
            $zt = 0;
        }
        else
        {
            $zt = 1;
        }
        Db::name('comments')
            ->where('id', Request::instance()->post('id'))
            ->update(['status' => $zt]);
        return true;
    }
    public function removeComment()
    {
        $this->checkPermissions(5);
        $post = Db::name('comments')
            ->where('id', Request::instance()->post('id'))
            ->field('post_id')
            ->find();
        Db::name('comments')
            ->where('id',Request::instance()->post('id'))
            ->delete();
        Db::name('posts')
            ->where('id', $post['post_id'])
            ->update([
                'comment_count' => ['exp','comment_count-1']
            ]);
        return true;
    }
    public function commentbatch()
    {
        $this->checkPermissions(5);
        $zhi = 0;
        switch(Request::instance()->post('cz')){
            case 'shenhe':
                $zhi = 1;
                break;
            case 'weishenhe':
                $zhi = 0;
                break;
        }
        Db::name('comments')
            ->where('id','in',Request::instance()->post('zcuan'))
            ->update(['status' => $zhi]);
        return true;
    }
    public function simiwz()
    {
        $this->checkUser();
        $this->checkPermissions(6);
        $data = Db::view('posts','id,post_date,post_title,post_status,comment_count,thumbnail,post_hits,istop,recommended')
            ->view('users','user_login','users.id=posts.post_author')
            ->where('post_author','=',Session::get($this->session_prefix.'user_id'))
            ->where('post_type',['=',0],['=',2],['=',3],['=',4],['=',5],['=',6],['=',7],['=',8],'or')
            ->where('post_status','=',2)
            ->where('status','=',1)
            ->order('post_date desc')
            ->paginate(10);
        $this->assign('data', $data);
        $this->assign('backstageMenu', 'neirong');
        $this->assign('option', 'simiwz');
        $this->assign('fenlei', $this->getfenlei());
        return $this->view();
    }
    public function gongkaiArticle()
    {
        $this->checkPermissions(6);
        Db::name('posts')
            ->where('id', Request::instance()->post('id'))
            ->where('post_author', Session::get($this->session_prefix.'user_id'))
            ->update(['post_status' => 1]);
        return true;
    }
    public function modifysimi()
    {
        $this->checkPermissions(6);
        $xiugai = '';
        $zhi = 0;
        switch(Request::instance()->post('cz')){
            case 'pgongkai':
                $xiugai = 'post_status';
                $zhi = 1;
                break;
            case 'pshanchu':
                $xiugai = 'status';
                $zhi = 0;
                break;
        }
        if(!empty($xiugai)){
            Db::name('posts')
                ->where('id','in',Request::instance()->post('zcuan'))
                ->where('post_author', Session::get($this->session_prefix.'user_id'))
                ->update([$xiugai => $zhi]);
        }
        return true;
    }
    public function searchsm()
    {
        $this->checkUser();
        $this->checkPermissions(6);
        $fenlei = 0;
        $start = '2000-01-01 01:01:01';
        $end = date("Y-m-d H:i:s");
        $key = '';
        if(Request::instance()->has('fenlei','get'))
        {
            $fenlei = Request::instance()->get('fenlei');
        }
        if(Request::instance()->has('start','get') && Request::instance()->get('start') != '')
        {
            $start = Request::instance()->get('start');
        }
        if(Request::instance()->has('end','get') && Request::instance()->get('end') != '')
        {
            $end = Request::instance()->get('end');
        }
        if(Request::instance()->has('key','get') && Request::instance()->get('key') != '')
        {
            $key = Request::instance()->get('key');
        }
        if(strtotime($start) > strtotime($end))
        {
            $tmp = $start;
            $start = $end;
            $end = $tmp;
        }
        if($fenlei != 0)
        {
            $data = Db::view('posts','id,post_date,post_title,post_status,comment_count,thumbnail,post_hits,istop,recommended,status')
                ->view('users','user_login','users.id=posts.post_author')
                ->view('term_relationships','term_id','term_relationships.object_id=posts.id')
                ->where('term_id','=',$fenlei)
                ->where('post_author','=',Session::get($this->session_prefix.'user_id'))
                ->where('post_type',['=',0],['=',2],['=',3],['=',4],['=',5],['=',6],['=',7],['=',8],'or')
                ->where('post_status','=',2)
                ->where('status','=',1)
                ->whereTime('post_date', 'between', [$start, $end])
                ->where('post_title|post_content','like','%'.$key.'%')
                ->order('post_date desc')
                ->paginate(10,false,[
                    'query' => [
                        'fenlei' => urlencode($fenlei),
                        'start' => urlencode($start),
                        'end' => urlencode($end),
                        'key' => urlencode($key)
                    ]
                ]);
        }
        else
        {
            $data = Db::view('posts','id,post_date,post_title,post_status,comment_count,thumbnail,post_hits,istop,recommended,status')
                ->view('users','user_login','users.id=posts.post_author')
                ->where('post_author','=',Session::get($this->session_prefix.'user_id'))
                ->where('post_type',['=',0],['=',2],['=',3],['=',4],['=',5],['=',6],['=',7],['=',8],'or')
                ->where('post_status','=',2)
                ->where('status','=',1)
                ->whereTime('post_date', 'between', [$start, $end])
                ->where('post_title|post_content','like','%'.$key.'%')
                ->order('post_date desc')
                ->paginate(10,false,[
                    'query' => [
                        'fenlei' => urlencode($fenlei),
                        'start' => urlencode($start),
                        'end' => urlencode($end),
                        'key' => urlencode($key)
                    ]
                ]);
        }
        $this->assign('data', $data);
        $this->assign('backstageMenu', 'neirong');
        $this->assign('option', 'simiwz');
        $this->assign('fenlei', $this->getfenlei());
        return $this->view('simiwz');
    }
    public function messages()
    {
        $this->checkUser();
        $this->checkPermissions(5);
        $data = Db::name('guestbook')->order('createtime desc')->paginate(10);
        $this->assign('data', $data);
        $this->assign('backstageMenu', 'yonghu');
        $this->assign('option', 'messages');
        return $this->view();
    }
    public function removeMessage()
    {
        $this->checkPermissions(5);
        Db::name('guestbook')->where('id',Request::instance()->post('id'))->delete();
        return true;
    }
    public function recycle()
    {
        $this->checkUser();
        $this->checkPermissions(6);
        if($this->permissions < 6)
        {
            $data = Db::view('posts','id,post_date,post_title,post_status,comment_count,thumbnail,post_hits,istop,recommended,status')
                ->view('users','user_login','users.id=posts.post_author')
                ->where('post_type',['=',0],['=',2],['=',3],['=',4],['=',5],['=',6],['=',7],['=',8],'or')
                ->where('status','=',0)
                ->order('post_date desc')
                ->paginate(10);
        }
        else
        {
            $data = Db::view('posts','id,post_date,post_title,post_status,comment_count,thumbnail,post_hits,istop,recommended,status')
                ->view('users','user_login','users.id=posts.post_author')
                ->where('post_author','=',Session::get($this->session_prefix.'user_id'))
                ->where('post_type',['=',0],['=',2],['=',3],['=',4],['=',5],['=',6],['=',7],['=',8],'or')
                ->where('status','=',0)
                ->order('post_date desc')
                ->paginate(10);
        }
        $this->assign('data', $data);
        $this->assign('backstageMenu', 'neirong');
        $this->assign('option', 'recycle');
        return $this->view();
    }
    public function removeArticle()
    {
        $this->checkPermissions(6);
        if($this->permissions > 5)
        {
            $re = Db::name('posts')->where('id',Request::instance()->post('id'))->where('post_author', Session::get($this->session_prefix.'user_id'))->delete();
            if(!empty($re))
            {
                Db::name('term_relationships')->where('object_id',Request::instance()->post('id'))->delete();
                Db::name('comments')->where('post_id',Request::instance()->post('id'))->delete();
            }
        }
        else
        {
            Db::name('posts')->where('id',Request::instance()->post('id'))->delete();
            Db::name('term_relationships')->where('object_id',Request::instance()->post('id'))->delete();
            Db::name('comments')->where('post_id',Request::instance()->post('id'))->delete();
        }
        return true;
    }
    public function reductionArticle()
    {
        $this->checkPermissions(6);
        if($this->permissions > 5)
        {
            Db::name('posts')
                ->where('id', Request::instance()->post('id'))
                ->where('post_author', Session::get($this->session_prefix.'user_id'))
                ->update(['status' => 1]);
        }
        else
        {
            Db::name('posts')
                ->where('id', Request::instance()->post('id'))
                ->update(['status' => 1]);
        }
        return true;
    }
    public function recycleBatch()
    {
        $this->checkPermissions(6);
        switch(Request::instance()->post('cz')){
            case 'phuanyuan':
                if($this->permissions > 5)
                {
                    Db::name('posts')
                        ->where('id','in',Request::instance()->post('zcuan'))
                        ->where('post_author', Session::get($this->session_prefix.'user_id'))
                        ->update(['status' => 1]);
                }
                else
                {
                    Db::name('posts')
                        ->where('id','in',Request::instance()->post('zcuan'))
                        ->update(['status' => 1]);
                }
                break;
            case 'pshanchu':
                if($this->permissions > 5)
                {
                    $re = Db::name('posts')->where('id','in',Request::instance()->post('zcuan'))->where('post_author', Session::get($this->session_prefix.'user_id'))->field('id')->select();
                    $idstr = '';
                    foreach($re as $val)
                    {
                        if($idstr == '')
                        {
                            $idstr = $val['id'];
                        }
                        else
                        {
                            $idstr .= ',' . $val['id'];
                        }
                    }
                    if(!empty($idstr))
                    {
                        Db::name('posts')->where('id','in',$idstr)->delete();
                        Db::name('term_relationships')->where('object_id','in',$idstr)->delete();
                        Db::name('comments')->where('post_id','in',$idstr)->delete();
                    }
                }
                else
                {
                    Db::name('posts')->where('id','in',Request::instance()->post('zcuan'))->delete();
                    Db::name('term_relationships')->where('object_id','in',Request::instance()->post('zcuan'))->delete();
                    Db::name('comments')->where('post_id','in',Request::instance()->post('zcuan'))->delete();
                }
                break;
        }
        return true;
    }
    public function search()
    {
        $this->checkUser();
        $this->checkPermissions(6);
        $fenlei = 0;
        $start = '2000-01-01 01:01:01';
        $end = date("Y-m-d H:i:s");
        $key = '';
        if(Request::instance()->has('fenlei','get'))
        {
            $fenlei = Request::instance()->get('fenlei');
        }
        if(Request::instance()->has('start','get') && Request::instance()->get('start') != '')
        {
            $start = Request::instance()->get('start');
        }
        if(Request::instance()->has('end','get') && Request::instance()->get('end') != '')
        {
            $end = Request::instance()->get('end');
        }
        if(Request::instance()->has('key','get') && Request::instance()->get('key') != '')
        {
            $key = Request::instance()->get('key');
        }
        if(strtotime($start) > strtotime($end))
        {
            $tmp = $start;
            $start = $end;
            $end = $tmp;
        }
        if($fenlei != 0)
        {
            if($this->permissions < 6)
            {
                $data = Db::view('posts','id,post_date,post_title,post_status,comment_count,thumbnail,post_hits,istop,recommended,status')
                    ->view('users','user_login','users.id=posts.post_author')
                    ->view('term_relationships','term_id','term_relationships.object_id=posts.id')
                    ->where('term_id','=',$fenlei)
                    ->where('post_type',['=',0],['=',2],['=',3],['=',4],['=',5],['=',6],['=',7],['=',8],'or')
                    ->where('status','=',1)
                    ->whereTime('post_date', 'between', [$start, $end])
                    ->where('post_title|post_content','like','%'.$key.'%')
                    ->order('post_date desc')
                    ->paginate(10,false,[
                        'query' => [
                            'fenlei' => urlencode($fenlei),
                            'start' => urlencode($start),
                            'end' => urlencode($end),
                            'key' => urlencode($key)
                        ]
                    ]);
                $this->assign('authority', 'all');
            }
            else
            {
                $data = Db::view('posts','id,post_date,post_title,post_status,comment_count,thumbnail,post_hits,istop,recommended,status')
                    ->view('users','user_login','users.id=posts.post_author')
                    ->view('term_relationships','term_id','term_relationships.object_id=posts.id')
                    ->where('term_id','=',$fenlei)
                    ->where('post_author','=',Session::get($this->session_prefix.'user_id'))
                    ->where('post_type',['=',0],['=',2],['=',3],['=',4],['=',5],['=',6],['=',7],['=',8],'or')
                    ->where('status','=',1)
                    ->whereTime('post_date', 'between', [$start, $end])
                    ->where('post_title|post_content','like','%'.$key.'%')
                    ->order('post_date desc')
                    ->paginate(10,false,[
                        'query' => [
                            'fenlei' => urlencode($fenlei),
                            'start' => urlencode($start),
                            'end' => urlencode($end),
                            'key' => urlencode($key)
                        ]
                    ]);
                $this->assign('authority', 'part');
            }
        }
        else
        {
            if($this->permissions < 6)
            {
                $data = Db::view('posts','id,post_date,post_title,post_status,comment_count,thumbnail,post_hits,istop,recommended,status')
                    ->view('users','user_login','users.id=posts.post_author')
                    ->where('post_type',['=',0],['=',2],['=',3],['=',4],['=',5],['=',6],['=',7],['=',8],'or')
                    ->where('status','=',1)
                    ->whereTime('post_date', 'between', [$start, $end])
                    ->where('post_title|post_content','like','%'.$key.'%')
                    ->order('post_date desc')
                    ->paginate(10,false,[
                        'query' => [
                            'fenlei' => urlencode($fenlei),
                            'start' => urlencode($start),
                            'end' => urlencode($end),
                            'key' => urlencode($key)
                        ]
                    ]);
                $this->assign('authority', 'all');
            }
            else
            {
                $data = Db::view('posts','id,post_date,post_title,post_status,comment_count,thumbnail,post_hits,istop,recommended,status')
                    ->view('users','user_login','users.id=posts.post_author')
                    ->where('post_author','=',Session::get($this->session_prefix.'user_id'))
                    ->where('post_type',['=',0],['=',2],['=',3],['=',4],['=',5],['=',6],['=',7],['=',8],'or')
                    ->where('status','=',1)
                    ->whereTime('post_date', 'between', [$start, $end])
                    ->where('post_title|post_content','like','%'.$key.'%')
                    ->order('post_date desc')
                    ->paginate(10,false,[
                        'query' => [
                            'fenlei' => urlencode($fenlei),
                            'start' => urlencode($start),
                            'end' => urlencode($end),
                            'key' => urlencode($key)
                        ]
                    ]);
                $this->assign('authority', 'part');
            }
        }
        $this->assign('data', $data);
        $this->assign('backstageMenu', 'neirong');
        $this->assign('option', 'articles');
        $this->assign('fenlei', $this->getfenlei());
        $this->assign('toutiao', intval($this->getb('toutiao')));
        return $this->view('articles');
    }
    public function recycleArticle()
    {
        $this->checkPermissions(6);
        if($this->permissions > 5)
        {
            Db::name('posts')
                ->where('id', Request::instance()->post('id'))
                ->where('post_author', Session::get($this->session_prefix.'user_id'))
                ->update(['status' => 0]);
        }
        else
        {
            Db::name('posts')
                ->where('id', Request::instance()->post('id'))
                ->update(['status' => 0]);
        }
        return true;
    }
    public function rewrite()
    {
        $this->checkUser();
        $this->checkPermissions(6);
        if(Request::instance()->has('postId','post'))
        {
            $rule = [
                'biaoti' => 'require',
                'neirong' => 'require'
            ];
            $msg = [
                'biaoti.require' => Lang::get('The title must be filled in'),
                'neirong.require' => Lang::get('Article content must be filled out')
            ];
            $data = [
                'biaoti' => Request::instance()->post('biaoti'),
                'neirong' => Request::instance()->post('neirong')
            ];
            $validate = new Validate($rule, $msg);
            if(!$validate->check($data))
            {
                $this->error($validate->getError());
                return false;
            }
            if($this->permissions > 5)
            {
                $us = Session::get($this->session_prefix.'user_id');
                $tmpart = Db::name('posts')->where('id',Request::instance()->post('postId'))->field('post_author')->find();
                if($tmpart['post_author'] != $us)
                {
                    $this->error(Lang::get('Your access rights are insufficient'));
                    exit();
                }
            }
            $neirong = str_replace('<img','<img class="img-responsive"',$this->filterJs(Request::instance()->post('neirong')));
            $guanjianci = str_replace('，',',',Request::instance()->post('guanjianci'));
            $suolvetu = Request::instance()->post('suolvetu');
            if(!$this->isLegalPicture($suolvetu, false))
            {
                $suolvetu = '';
            }
            $shenhe = 1;
            if(Request::instance()->has('simiwenzhang','post') && Request::instance()->post('simiwenzhang') == 'on')
            {
                $shenhe = 2;
            }
            $data = ['post_keywords' => htmlspecialchars($guanjianci), 'post_source' => htmlspecialchars(Request::instance()->post('laiyuan')), 'post_content' => $neirong, 'post_title' => htmlspecialchars(Request::instance()->post('biaoti')), 'post_excerpt' => htmlspecialchars(Request::instance()->post('zhaiyao')), 'post_status' => $shenhe, 'comment_status' => Request::instance()->post('pinglun'), 'post_modified' => date("Y-m-d H:i:s"), 'post_type' => Request::instance()->post('xingshi'), 'thumbnail' => $suolvetu, 'istop' => Request::instance()->post('zhiding'), 'recommended' => Request::instance()->post('tuijian')];
            Db::name('posts')
                ->where('id', Request::instance()->post('postId'))
                ->update($data);
            Db::name('term_relationships')->where('object_id',Request::instance()->post('postId'))->delete();
            if(isset($_POST['fenlei']) && is_array($_POST['fenlei']))
            {
                $data = [];
                foreach($_POST['fenlei'] as $key => $val)
                {
                    $data[] = ['object_id' => Request::instance()->post('postId'), 'term_id' => $val];
                }
                Db::name('term_relationships')->insertAll($data);
            }
            Hook::add('rewrite_post',$this->plugins);
            $params = [
                'id' => Request::instance()->post('postId')
            ];
            Hook::listen('rewrite_post',$params,$this->ccc);
            Hook::add('rewrite_post_later',$this->plugins);
            Hook::listen('rewrite_post_later',$params,$this->ccc);
        }
        $classify = Db::name('term_relationships')->field('term_id')->where('object_id',Request::instance()->get('art'))->select();
        $fenlei =$this->getfenlei();
        foreach($fenlei as $key => $val){
            $fenlei[$key]['classify'] = 0;
            foreach($classify as $cval){
                if($val['id'] == $cval['term_id']){
                    $fenlei[$key]['classify'] = 1;
                    break;
                }
            }
        }
        $wzid = 0;
        if(Request::instance()->has('postId','post'))
        {
            $wzid = Request::instance()->post('postId');
        }
        elseif(Request::instance()->has('art','get'))
        {
            $wzid = Request::instance()->get('art');
        }
        $data = Db::name('posts')->where('id',$wzid)->find();
        $us = Session::get($this->session_prefix.'user_id');
        if($this->permissions > 5)
        {
            if($data['post_author'] != $us)
            {
                $this->error(Lang::get('Your access rights are insufficient'));
                exit();
            }
        }
        $self = 0;
        if($data['post_author'] == $us)
        {
            $self = 1;
        }
        $this->assign('self', $self);
        $data['post_content'] = str_replace('<img class="img-responsive"','<img',$data['post_content']);
        $editor = 'HandyEditor';
        if(Request::instance()->has('editor','get'))
        {
            $editor = Request::instance()->get('editor');
            $this->setb('whichEditorToUse',$editor);
        }
        else
        {
            $whichEditorToUse = $this->getb('whichEditorToUse');
            if(!empty($whichEditorToUse))
            {
                $editor = $whichEditorToUse;
            }
        }
        $leditor = strtolower($editor);
        $this->assign('whichEditor', $leditor);
        if($leditor == 'handyeditor' || $leditor == 'kindeditor')
        {
            $data['post_content'] = str_replace('&','&amp;',$data['post_content']);
        }
        $this->assign('data', $data);
        $this->writeAlias($wzid);
        $this->writeAppend($wzid);
        $this->writeAfterEditor($wzid);
        $this->writeRightAppend($wzid);
        $this->switchEditor();
        $this->assign('backstageMenu', 'neirong');
        $this->assign('option', 'articles');
        $this->assign('fenlei', $fenlei);
        return $this->view();
    }
    public function modify()
    {
        $this->checkPermissions(6);
        $xiugai = '';
        $zhi = 0;
        switch(Request::instance()->post('cz')){
            case 'shenhe':
                $xiugai = 'post_status';
                $zhi = 1;
                break;
            case 'weishenhe':
                $xiugai = 'post_status';
                $zhi = 0;
                break;
            case 'zhiding':
                $xiugai = 'istop';
                $zhi = 1;
                break;
            case 'weizhiding':
                $xiugai = 'istop';
                $zhi = 0;
                break;
            case 'tuijian':
                $xiugai = 'recommended';
                $zhi = 1;
                break;
            case 'weituijian':
                $xiugai = 'recommended';
                $zhi = 0;
                break;
            case 'pshanchu':
                $xiugai = 'status';
                $zhi = 0;
                break;
        }
        if(!empty($xiugai)){
            if($this->permissions > 5)
            {
                if($xiugai == 'status')
                {
                    Db::name('posts')
                        ->where('id','in',Request::instance()->post('zcuan'))
                        ->where('post_author', Session::get($this->session_prefix.'user_id'))
                        ->update([$xiugai => $zhi]);
                }
            }
            else
            {
                Db::name('posts')
                    ->where('id','in',Request::instance()->post('zcuan'))
                    ->update([$xiugai => $zhi]);
            }
        }
        return true;
    }
    public function newpage()
    {
        $this->checkUser();
        $this->checkPermissions(3);
        $template = Db::name('options')
            ->where('option_name','template')
            ->field('option_value')
            ->find();
        $dir = glob(APP_PATH.'../public/'.$template['option_value'].'/page/*.html');
        Lang::load(APP_PATH . '../public/'.$template['option_value'].'/lang/'.$this->lang.'.php');
        $remark = [];
        foreach($dir as $key => $val)
        {
            $tmpdir = basename($val);
            $houzhui = substr(strrchr($tmpdir, '.'), 1);
            $tmpdir = basename($tmpdir,".".$houzhui);
            $dir[$key] = $tmpdir;
            $pageStr = file_get_contents($val);
            if(preg_match("/<!--([\s\S]+?)-->/i", $pageStr ,$matches))
            {
                $remark[$tmpdir] = Lang::get(trim($matches[1]));
            }
        }
        if(Request::instance()->has('biaoti','post'))
        {
            $rule = [
                'biaoti' => 'require',
                'template' => 'require'
            ];
            $msg = [
                'biaoti.require' => Lang::get('The title must be filled in'),
                'template.require' => Lang::get('The template must be selected')
            ];
            $data = [
                'biaoti' => Request::instance()->post('biaoti'),
                'template' => Request::instance()->post('template')
            ];
            $validate = new Validate($rule, $msg);
            if(!$validate->check($data))
            {
                $this->error($validate->getError());
                return false;
            }
            $guanjianci = str_replace('，',',',Request::instance()->post('guanjianci'));
            $neirong = str_replace('<img','<img class="img-responsive"',$this->filterJs(Request::instance()->post('neirong')));
            $fabushijian = date("Y-m-d H:i:s");
            $suolvetu = Request::instance()->post('suolvetu');
            if(!$this->isLegalPicture($suolvetu, false))
            {
                $suolvetu = '';
            }
            $template = Request::instance()->post('template');
            if(in_array($template,$dir))
            {
                $hz = strrchr($template, '.');
                if($hz === false || ($hz != '.html' && $hz != '.htm'))
                {
                    $template = $template . '.html';
                }
                $data = ['post_author' => Session::get($this->session_prefix.'user_id'), 'post_keywords' => htmlspecialchars($guanjianci), 'post_date' => $fabushijian, 'post_content' => $neirong, 'post_title' => htmlspecialchars(Request::instance()->post('biaoti')), 'post_excerpt' => htmlspecialchars(Request::instance()->post('zhaiyao')), 'post_modified' => $fabushijian, 'post_type' => 1, 'thumbnail' => $suolvetu, 'template' => $template];
                $id = Db::name('posts')->insertGetId($data);
                $fenlei = Request::instance()->post('fenlei');
                if(!empty($fenlei))
                {
                    $meiyexianshi = Request::instance()->post('meiyexianshi');
                    $this->insertBindingCategory($id,intval($fenlei),intval($meiyexianshi));
                }
            }
        }
        $this->assign('dir', $dir);
        $this->assign('remark', json_encode($remark));
        $editor = 'HandyEditor';
        if(Request::instance()->has('editor','get'))
        {
            $editor = Request::instance()->get('editor');
            $this->setb('whichEditorToUse',$editor);
        }
        else
        {
            $whichEditorToUse = $this->getb('whichEditorToUse');
            if(!empty($whichEditorToUse))
            {
                $editor = $whichEditorToUse;
            }
        }
        $this->assign('whichEditor', strtolower($editor));
        $this->switchEditor();
        $this->assign('backstageMenu', 'yemian');
        $this->assign('option', 'newpage');
        $this->assign('fenlei', $this->getfenlei());
        return $this->view();
    }
    public function allpage()
    {
        $this->checkUser();
        $this->checkPermissions(3);
        $data = Db::view('posts','id,post_date,post_title,thumbnail,template')
            ->view('users','user_login','users.id=posts.post_author')
            ->where('post_type','=',1)
            ->where('status','=',1)
            ->order('post_date desc')
            ->paginate(10);
        $this->assign('data', $data);
        $this->assign('backstageMenu', 'yemian');
        $this->assign('option', 'allpage');
        $this->assign('zhuyemian', intval($this->getb('zhuyemian')));
        return $this->view();
    }
    public function removePage()
    {
        $this->checkPermissions(3);
        Db::name('posts')
            ->where('id',Request::instance()->post('id'))
            ->delete();
        return true;
    }
    public function removeAllPage()
    {
        $this->checkPermissions(3);
        if(Request::instance()->post('cz') == 'pshanchu')
        {
            Db::name('posts')
                ->where('id', 'in', Request::instance()->post('zcuan'))
                ->delete();
        }
        return true;
    }
    public function editpage()
    {
        $this->checkUser();
        $this->checkPermissions(3);
        $template = Db::name('options')
            ->where('option_name','template')
            ->field('option_value')
            ->find();
        $dir = glob(APP_PATH.'../public/'.$template['option_value'].'/page/*.html');
        Lang::load(APP_PATH . '../public/'.$template['option_value'].'/lang/'.$this->lang.'.php');
        $remark = [];
        foreach($dir as $key => $val)
        {
            $tmpdir = basename($val);
            $houzhui = substr(strrchr($tmpdir, '.'), 1);
            $tmpdir = basename($tmpdir,".".$houzhui);
            $dir[$key] = $tmpdir;
            $pageStr = file_get_contents($val);
            if(preg_match("/<!--([\s\S]+?)-->/i", $pageStr ,$matches))
            {
                $remark[$tmpdir] = Lang::get(trim($matches[1]));
            }
        }
        if(Request::instance()->has('postId','post'))
        {
            $rule = [
                'biaoti' => 'require',
                'template' => 'require'
            ];
            $msg = [
                'biaoti.require' => Lang::get('The title must be filled in'),
                'template.require' => Lang::get('The template must be selected')
            ];
            $data = [
                'biaoti' => Request::instance()->post('biaoti'),
                'template' => Request::instance()->post('template')
            ];
            $validate = new Validate($rule, $msg);
            if(!$validate->check($data))
            {
                $this->error($validate->getError());
                return false;
            }
            $guanjianci = str_replace('，',',',Request::instance()->post('guanjianci'));
            $neirong = str_replace('<img','<img class="img-responsive"',$this->filterJs(Request::instance()->post('neirong')));
            $suolvetu = Request::instance()->post('suolvetu');
            if(!$this->isLegalPicture($suolvetu, false))
            {
                $suolvetu = '';
            }
            $template = Request::instance()->post('template');
            if(in_array($template,$dir))
            {
                $hz = strrchr($template, '.');
                if($hz === false || ($hz != '.html' && $hz != '.htm'))
                {
                    $template = $template . '.html';
                }
                $pid = Request::instance()->post('postId');
                $data = ['post_author' => Session::get($this->session_prefix.'user_id'), 'post_keywords' => htmlspecialchars($guanjianci), 'post_content' => $neirong, 'post_title' => htmlspecialchars(Request::instance()->post('biaoti')), 'post_excerpt' => htmlspecialchars(Request::instance()->post('zhaiyao')), 'post_modified' => date("Y-m-d H:i:s"), 'thumbnail' => $suolvetu, 'template' => $template];
                Db::name('posts')
                    ->where('id', $pid)
                    ->update($data);
                $fenlei = Request::instance()->post('fenlei');
                $meiyexianshi = Request::instance()->post('meiyexianshi');
                $this->updatedBindingCategory($pid,$pid,intval($fenlei),intval($meiyexianshi));
            }
        }
        $wzid = 0;
        if(Request::instance()->has('postId','post'))
        {
            $wzid = Request::instance()->post('postId');
        }
        elseif(Request::instance()->has('art','get'))
        {
            $wzid = Request::instance()->get('art');
        }
        $data = Db::name('posts')->where('id',$wzid)->find();
        $data['post_content'] = str_replace('<img class="img-responsive"','<img',$data['post_content']);
        $houzhui = substr(strrchr($data['template'], '.'), 1);
        $data['template'] = basename($data['template'],".".$houzhui);
        $editor = 'HandyEditor';
        if(Request::instance()->has('editor','get'))
        {
            $editor = Request::instance()->get('editor');
            $this->setb('whichEditorToUse',$editor);
        }
        else
        {
            $whichEditorToUse = $this->getb('whichEditorToUse');
            if(!empty($whichEditorToUse))
            {
                $editor = $whichEditorToUse;
            }
        }
        $leditor = strtolower($editor);
        $this->assign('whichEditor', $leditor);
        if($leditor == 'handyeditor' || $leditor == 'kindeditor')
        {
            $data['post_content'] = str_replace('&','&amp;',$data['post_content']);
        }
        $this->assign('data', $data);
        $classify = $this->findBindingCategory(Request::instance()->get('art'));
        $myxs = 10;
        if(is_array($classify))
        {
            $myxs = $classify['mys'];
            $classify = $classify['fl'];
        }
        $fenlei =$this->getfenlei();
        foreach($fenlei as $key => $val){
            if($val['id'] == $classify)
            {
                $fenlei[$key]['classify'] = 1;
            }
            else
            {
                $fenlei[$key]['classify'] = 0;
            }
        }
        $this->assign('dir', $dir);
        $this->assign('remark', json_encode($remark));
        $this->switchEditor();
        $this->assign('backstageMenu', 'yemian');
        $this->assign('option', 'allpage');
        $this->assign('myxs', $myxs);
        $this->assign('fenlei', $fenlei);
        return $this->view();
    }
    public function searchpage()
    {
        $this->checkUser();
        $this->checkPermissions(3);
        $start = '2000-01-01 01:01:01';
        $end = date("Y-m-d H:i:s");
        $key = '';
        if(Request::instance()->has('start','get') && Request::instance()->get('start') != '')
        {
            $start = Request::instance()->get('start');
        }
        if(Request::instance()->has('end','get') && Request::instance()->get('end') != '')
        {
            $end = Request::instance()->get('end');
        }
        if(Request::instance()->has('key','get') && Request::instance()->get('key') != '')
        {
            $key = Request::instance()->get('key');
        }
        if(strtotime($start) > strtotime($end))
        {
            $tmp = $start;
            $start = $end;
            $end = $tmp;
        }
        $data = Db::view('posts','id,post_date,post_title,thumbnail,template')
            ->view('users','user_login','users.id=posts.post_author')
            ->where('post_type','=',1)
            ->where('status','=',1)
            ->whereTime('post_date', 'between', [$start, $end])
            ->where('post_title|post_content','like','%'.$key.'%')
            ->order('post_date desc')
            ->paginate(10,false,[
                'query' => [
                    'start' => urlencode($start),
                    'end' => urlencode($end),
                    'key' => urlencode($key)
                ]
            ]);
        $this->assign('data', $data);
        $this->assign('backstageMenu', 'yemian');
        $this->assign('option', 'allpage');
        $this->assign('zhuyemian', intval($this->getb('zhuyemian')));
        return $this->view('allpage');
    }
    public function addSlideshow()
    {
        $this->checkUser();
        $this->checkPermissions(3);
        if(Request::instance()->isPost())
        {
            $rule = [
                'slideshow' => 'require'
            ];
            $msg = [
                'slideshow.require' => Lang::get('Image must be uploaded')
            ];
            $data = [
                'slideshow' => Request::instance()->post('slideshow')
            ];
            $validate = new Validate($rule, $msg);
            if(!$validate->check($data))
            {
                $this->error($validate->getError());
                return false;
            }
            $data = ['slide_name' => htmlspecialchars(Request::instance()->post('mingcheng')), 'slide_pic' => Request::instance()->post('slideshow'), 'slide_url' => htmlspecialchars(Request::instance()->post('lianjie')), 'slide_des' => htmlspecialchars(Request::instance()->post('miaoshu'))];
            Db::name('slide')->insert($data);
        }
        $slideshowWidth = Db::name('options')->where('option_name','slideshowWidth')->field('option_value')->find();
        $slideshowHeight = Db::name('options')->where('option_name','slideshowHeight')->field('option_value')->find();
        $this->assign('slideshowWidth', $slideshowWidth['option_value']);
        $this->assign('slideshowHeight', $slideshowHeight['option_value']);
        $this->assign('backstageMenu', 'yemian');
        $this->assign('option', 'addSlideshow');
        return $this->view();
    }
    public function modifyslide()
    {
        $this->checkUser();
        $this->checkPermissions(3);
        if(Request::instance()->isPost())
        {
            $rule = [
                'slideshow' => 'require'
            ];
            $msg = [
                'slideshow.require' => Lang::get('Image must be uploaded')
            ];
            $data = [
                'slideshow' => Request::instance()->post('slideshow')
            ];
            $validate = new Validate($rule, $msg);
            if(!$validate->check($data))
            {
                $this->error($validate->getError());
                return false;
            }
            $data = ['slide_name' => htmlspecialchars(Request::instance()->post('mingcheng')), 'slide_pic' => Request::instance()->post('slideshow'), 'slide_url' => htmlspecialchars(Request::instance()->post('lianjie')), 'slide_des' => htmlspecialchars(Request::instance()->post('miaoshu'))];
            Db::name('slide')
                ->where('slide_id', Request::instance()->post('slideId'))
                ->update($data);
        }
        $wzid = 0;
        if(Request::instance()->has('slideId','post'))
        {
            $wzid = Request::instance()->post('slideId');
        }
        elseif(Request::instance()->has('c','get'))
        {
            $wzid = Request::instance()->get('c');
        }
        $data = Db::name('slide')->where('slide_id',$wzid)->find();
        $this->assign('data', $data);
        $slideshowWidth = Db::name('options')->where('option_name','slideshowWidth')->field('option_value')->find();
        $slideshowHeight = Db::name('options')->where('option_name','slideshowHeight')->field('option_value')->find();
        $this->assign('slideshowWidth', $slideshowWidth['option_value']);
        $this->assign('slideshowHeight', $slideshowHeight['option_value']);
        $this->assign('backstageMenu', 'yemian');
        $this->assign('option', 'slideshow');
        return $this->view();
    }
    public function slideshow()
    {
        $this->checkUser();
        $this->checkPermissions(3);
        if(Request::instance()->has('paixu','post'))
        {
            $paixu = Request::instance()->post();
            foreach($paixu as $key => $val)
            {
                if($val != 'paixu')
                {
                    Db::name('slide')
                        ->where('slide_id', $key)
                        ->update(['listorder' => intval($val)]);
                }
            }
        }
        $data = Db::name('slide')->order('listorder')->select();
        $this->assign('data', $data);
        $this->assign('backstageMenu', 'yemian');
        $this->assign('option', 'slideshow');
        return $this->view();
    }
    public function removeSlide()
    {
        $this->checkPermissions(3);
        $slide = Db::name('slide')
            ->where('slide_id', Request::instance()->post('id'))
            ->field('slide_pic')
            ->find();
        $yuming = Db::name('options')->where('option_name','domain')->field('option_value')->find();
        $yfile = str_replace($yuming['option_value'],'',$slide['slide_pic']);
        if(!empty($yfile) && $this->isLegalPicture('/'.$yfile)){
            $yfile = substr($yfile,0,1)=='/' ? substr($yfile,1) : $yfile;
            $yfile = str_replace("/", DS, $yfile);
            @unlink(APP_PATH . '..'. DS . $yfile);
        }
        Db::name('slide')
            ->where('slide_id',Request::instance()->post('id'))
            ->delete();
        return true;
    }
    public function yincang_qiyong()
    {
        $this->checkPermissions(3);
        $zt = Request::instance()->post('zt');
        if($zt == 1)
        {
            $zt = 0;
        }
        else
        {
            $zt = 1;
        }
        Db::name('slide')
            ->where('slide_id', Request::instance()->post('id'))
            ->update(['slide_status' => $zt]);
        return true;
    }
    public function addLinks()
    {
        $this->checkUser();
        $this->checkPermissions(3);
        if(Request::instance()->isPost())
        {
            $rule = [
                'mingcheng' => 'require',
                'dizhi' => 'require'
            ];
            $msg = [
                'mingcheng.require' => Lang::get('Friendship link name is required'),
                'dizhi.require' => Lang::get('Friendship link address is required')
            ];
            $data = [
                'mingcheng' => Request::instance()->post('mingcheng'),
                'dizhi' => Request::instance()->post('dizhi')
            ];
            $validate = new Validate($rule, $msg);
            if(!$validate->check($data))
            {
                $this->error($validate->getError());
                return false;
            }
            $weizhi = 0;
            if(Request::instance()->has('shouye','post') && Request::instance()->post('shouye') == 'on')
            {
                $weizhi = 1;
            }
            $data = ['link_url' => htmlspecialchars(Request::instance()->post('dizhi')), 'link_name' => htmlspecialchars(Request::instance()->post('mingcheng')), 'link_image' => Request::instance()->post('tubiao'), 'link_target' => Request::instance()->post('dakai'), 'link_description' => htmlspecialchars(Request::instance()->post('miaoshu')), 'link_location' => $weizhi];
            Db::name('links')->insert($data);
        }
        $this->assign('backstageMenu', 'yemian');
        $this->assign('option', 'addLinks');
        return $this->view();
    }
    public function links()
    {
        $this->checkUser();
        $this->checkPermissions(3);
        if(Request::instance()->has('paixu','post'))
        {
            $paixu = Request::instance()->post();
            foreach($paixu as $key => $val)
            {
                if($val != 'paixu')
                {
                    Db::name('links')
                        ->where('link_id', $key)
                        ->update(['listorder' => intval($val)]);
                }
            }
        }
        $data = Db::name('links')->order('listorder')->select();
        $this->assign('data', $data);
        $this->assign('backstageMenu', 'yemian');
        $this->assign('option', 'links');
        return $this->view();
    }
    public function modifylink()
    {
        $this->checkUser();
        $this->checkPermissions(3);
        if(Request::instance()->isPost())
        {
            $rule = [
                'mingcheng' => 'require',
                'dizhi' => 'require'
            ];
            $msg = [
                'mingcheng.require' => Lang::get('Friendship link name is required'),
                'dizhi.require' => Lang::get('Friendship link address is required')
            ];
            $data = [
                'mingcheng' => Request::instance()->post('mingcheng'),
                'dizhi' => Request::instance()->post('dizhi')
            ];
            $validate = new Validate($rule, $msg);
            if(!$validate->check($data))
            {
                $this->error($validate->getError());
                return false;
            }
            $weizhi = 0;
            if(Request::instance()->has('shouye','post') && Request::instance()->post('shouye') == 'on')
            {
                $weizhi = 1;
            }
            $data = ['link_url' => htmlspecialchars(Request::instance()->post('dizhi')), 'link_name' => htmlspecialchars(Request::instance()->post('mingcheng')), 'link_image' => Request::instance()->post('tubiao'), 'link_target' => Request::instance()->post('dakai'), 'link_description' => htmlspecialchars(Request::instance()->post('miaoshu')), 'link_location' => $weizhi];
            Db::name('links')
                ->where('link_id', Request::instance()->post('linkId'))
                ->update($data);
        }
        $wzid = 0;
        if(Request::instance()->has('linkId','post'))
        {
            $wzid = Request::instance()->post('linkId');
        }
        elseif(Request::instance()->has('c','get'))
        {
            $wzid = Request::instance()->get('c');
        }
        $data = Db::name('links')->where('link_id',$wzid)->find();
        $this->assign('data', $data);
        $this->assign('backstageMenu', 'yemian');
        $this->assign('option', 'links');
        return $this->view();
    }
    public function link_yincang_qiyong()
    {
        $this->checkPermissions(3);
        $zt = Request::instance()->post('zt');
        if($zt == 1)
        {
            $zt = 0;
        }
        else
        {
            $zt = 1;
        }
        Db::name('links')
            ->where('link_id', Request::instance()->post('id'))
            ->update(['link_status' => $zt]);
        return true;
    }
    public function removeLink()
    {
        $this->checkPermissions(3);
        $slide = Db::name('links')
            ->where('link_id', Request::instance()->post('id'))
            ->field('link_image')
            ->find();
        $yuming = Db::name('options')->where('option_name','domain')->field('option_value')->find();
        $yfile = str_replace($yuming['option_value'],'',$slide['link_image']);
        if(!empty($yfile) && $this->isLegalPicture($slide['link_image'])){
            $yfile = substr($yfile,0,1)=='/' ? substr($yfile,1) : $yfile;
            $yfile = str_replace("/", DS, $yfile);
            @unlink(APP_PATH . '..'. DS . $yfile);
        }
        Db::name('links')
            ->where('link_id',Request::instance()->post('id'))
            ->delete();
        return true;
    }
    public function general()
    {
        $this->checkUser();
        $this->checkPermissions(3);
        $data = Db::name('users')->where('user_type',['=',6],['=',7],'or')->order('last_login_time desc')->paginate(10);
        $this->assign('data', $data);
        $this->assign('backstageMenu', 'yonghu');
        $this->assign('option', 'general');
        return $this->view();
    }
    public function jiangquan()
    {
        $this->checkPermissions(3);
        Db::name('users')
            ->where('id', Request::instance()->post('id'))
            ->update(['user_type' => 7]);
        echo Lang::get('reader');
        exit();
    }
    public function tiquan()
    {
        $this->checkPermissions(3);
        Db::name('users')
            ->where('id', Request::instance()->post('id'))
            ->update(['user_type' => 6]);
        echo Lang::get('author');
        exit();
    }
    public function searchuser()
    {
        $this->checkUser();
        $this->checkPermissions(3);
        $data = [];
        if(Request::instance()->has('user','get'))
        {
            $user = Request::instance()->get('user');
            $user = trim($user);
            $data = Db::name('users')
                ->where('user_type',7)
                ->where('user_login|user_nicename','like','%'.$user.'%')
                ->order('last_login_time desc')
                ->paginate(10,false,[
                    'query' => [
                        'user' => urlencode($user)
                    ]
                ]);
        }
        $this->assign('data', $data);
        $this->assign('backstageMenu', 'yonghu');
        $this->assign('option', 'general');
        return $this->view('general');
    }
    public function lahei_qiyong()
    {
        $this->checkPermissions(3);
        $zt = Request::instance()->post('zt');
        if($zt == 1)
        {
            $zt = 0;
        }
        else
        {
            $zt = 1;
        }
        Db::name('users')
            ->where('id', Request::instance()->post('id'))
            ->update(['user_status' => $zt]);
        return true;
    }
    public function category()
    {
        $this->checkUser();
        $this->checkPermissions(3);
        if(Request::instance()->has('fenleiming','post'))
        {
            $rule = [
                'fenleiming' => 'require'
            ];
            $msg = [
                'fenleiming.require' => Lang::get('The category name must be filled in')
            ];
            $data = [
                'fenleiming' => Request::instance()->post('fenleiming')
            ];
            $validate = new Validate($rule, $msg);
            if(!$validate->check($data))
            {
                $this->error($validate->getError());
                return false;
            }
            $active = 0;
            if(Request::instance()->has('zhucaidan','post') && Request::instance()->post('zhucaidan') == 'on')
            {
                $active = 1;
            }
            $data = ['nav_name' => htmlspecialchars(Request::instance()->post('fenleiming')), 'active' => $active, 'remark' => htmlspecialchars(Request::instance()->post('miaoshu'))];
            $id = Db::name('nav_cat')->insertGetId($data);
            if($active == 1)
            {
                Db::name('nav_cat')
                    ->where('navcid', 'neq', $id)
                    ->update(['active' => 0]);
            }
        }
        $this->assign('backstageMenu', 'caidan');
        $this->assign('option', 'category');
        return $this->view();
    }
    public function managemc()
    {
        $this->checkUser();
        $this->checkPermissions(3);
        $data = Db::name('nav_cat')->select();
        $this->assign('data', $data);
        $this->assign('backstageMenu', 'caidan');
        $this->assign('option', 'managemc');
        return $this->view();
    }
    public function modifycategory()
    {
        $this->checkUser();
        $this->checkPermissions(3);
        if(Request::instance()->has('navcid','post'))
        {
            $rule = [
                'fenleiming' => 'require'
            ];
            $msg = [
                'fenleiming.require' => Lang::get('The category name must be filled in')
            ];
            $data = [
                'fenleiming' => Request::instance()->post('fenleiming')
            ];
            $validate = new Validate($rule, $msg);
            if(!$validate->check($data))
            {
                $this->error($validate->getError());
                return false;
            }
            $active = 0;
            if(Request::instance()->has('zhucaidan','post') && Request::instance()->post('zhucaidan') == 'on')
            {
                $active = 1;
            }
            $data = ['nav_name' => htmlspecialchars(Request::instance()->post('fenleiming')), 'active' => $active, 'remark' => htmlspecialchars(Request::instance()->post('miaoshu'))];
            Db::name('nav_cat')
                ->where('navcid', Request::instance()->post('navcid'))
                ->update($data);
            if($active == 1)
            {
                Db::name('nav_cat')
                    ->where('navcid', 'neq', Request::instance()->post('navcid'))
                    ->update(['active' => 0]);
            }
        }
        $wzid = 0;
        if(Request::instance()->has('navcid','post'))
        {
            $wzid = Request::instance()->post('navcid');
        }
        elseif(Request::instance()->has('c','get'))
        {
            $wzid = Request::instance()->get('c');
        }
        $data = Db::name('nav_cat')->where('navcid',$wzid)->find();
        $this->assign('data', $data);
        $this->assign('backstageMenu', 'caidan');
        $this->assign('option', 'managemc');
        return $this->view();
    }
    public function removemanagemc()
    {
        $this->checkPermissions(3);
        Db::name('nav_cat')
            ->where('navcid',Request::instance()->post('id'))
            ->delete();
        return true;
    }
    public function addmenu()
    {
        $this->checkUser();
        $this->checkPermissions(3);
        if(Request::instance()->has('caidanming','post'))
        {
            $rule = [
                'caidanming' => 'require'
            ];
            $msg = [
                'caidanming.require' => Lang::get('The menu name must be filled in')
            ];
            $data = [
                'caidanming' => Request::instance()->post('caidanming')
            ];
            $validate = new Validate($rule, $msg);
            if(!$validate->check($data))
            {
                $this->error($validate->getError());
                return false;
            }
            $lianjie = '';
            $tmp = $this->filterJavascript(trim(Request::instance()->post('zidingyi')));
            if($tmp != '')
            {
                if(substr($tmp,0,1)=='#'){
                    $lianjie = Request::instance()->post('lianjie').$tmp;
                }
                else{
                    $lianjie = (substr($tmp,0,4)=='http' || substr($tmp,0,5)=='https' || $this->doNothing($tmp)) ? $tmp : 'http://'.$tmp;
                }
            }
            else
            {
                $lianjie = Request::instance()->post('lianjie');
            }
            $data = ['cid' => Request::instance()->post('caidanfenlei'), 'parent_id' => Request::instance()->post('fuji'), 'label' => htmlspecialchars(Request::instance()->post('caidanming')), 'target' => Request::instance()->post('dakaifangshi'), 'href' => $lianjie, 'icon' => $this->filterJavascript(Request::instance()->post('tubiao')), 'status' => Request::instance()->post('zhuangtai')];
            $vid = Db::name('nav')->insertGetId($data);
            Hook::add('menu_append_post',$this->plugins);
            $params = [
                'id' => $vid
            ];
            Hook::listen('menu_append_post',$params,$this->ccc);
        }
        $cid = 0;
        if(Request::instance()->has('cid','get'))
        {
            $cid = Request::instance()->get('cid');
        }
        $this->assign('cid', $cid);
        if($cid == 0)
        {
            $nav = Db::name('nav_cat')->field('navcid')->order('active desc')->limit(1)->find();
            $cid = $nav['navcid'];
        }
        $this->addModifyMenu($cid);
        $fj = 0;
        if(Request::instance()->has('c','get'))
        {
            $fj = Request::instance()->get('c');
        }
        $this->assign('fj', $fj);
        $this->menuAppend(0);
        $this->assign('backstageMenu', 'caidan');
        $this->assign('option', 'addmenu');
        return $this->view();
    }
    public function changeParent()
    {
        $this->checkPermissions(3);
        $caidan = Db::name('nav')->where('cid', Request::instance()->post('id'))->where('status', 1)->field('id,parent_id,label')->order('listorder')->select();
        echo '<option value="0">'.Lang::get('As a first-level menu').'</option>';
        if(is_array($caidan) && count($caidan) > 0)
        {
            $caidan = Tree::makeTreeForHtml($caidan);
            foreach($caidan as $key => $val){
                if($val['id'] == Request::instance()->post('fj'))
                {
                    echo '<option value="'.$val['id'].'" selected>'.str_repeat('&#12288;',$val['level']);
                    if($val['level'] > 0){
                        echo '└&nbsp;';
                    }
                    echo $val['label'].'</option>';
                }
                else
                {
                    echo '<option value="'.$val['id'].'">'.str_repeat('&#12288;',$val['level']);
                    if($val['level'] > 0){
                        echo '└&nbsp;';
                    }
                    echo $val['label'].'</option>';
                }
            }
        }
        exit;
    }
    public function modifymenu()
    {
        $this->checkUser();
        $this->checkPermissions(3);
        if(Request::instance()->has('caidanId','post'))
        {
            $rule = [
                'caidanming' => 'require'
            ];
            $msg = [
                'caidanming.require' => Lang::get('The menu name must be filled in')
            ];
            $data = [
                'caidanming' => Request::instance()->post('caidanming')
            ];
            $validate = new Validate($rule, $msg);
            if(!$validate->check($data))
            {
                $this->error($validate->getError());
                return false;
            }
            $lianjie = '';
            $tmp = $this->filterJavascript(trim(Request::instance()->post('zidingyi')));
            if($tmp != '')
            {
                if(substr($tmp,0,1)=='#'){
                    $lianjie = Request::instance()->post('lianjie').$tmp;
                }
                else{
                    $lianjie = (substr($tmp,0,4)=='http' || substr($tmp,0,5)=='https' || $this->doNothing($tmp)) ? $tmp : 'http://'.$tmp;
                }
            }
            else
            {
                $lianjie = Request::instance()->post('lianjie');
            }
            $data = ['cid' => Request::instance()->post('caidanfenlei'), 'parent_id' => Request::instance()->post('fuji'), 'label' => htmlspecialchars(Request::instance()->post('caidanming')), 'target' => Request::instance()->post('dakaifangshi'), 'href' => $lianjie, 'icon' => $this->filterJavascript(Request::instance()->post('tubiao')), 'status' => Request::instance()->post('zhuangtai')];
            $id = Request::instance()->post('caidanId');
            Db::name('nav')
                ->where('id', $id)
                ->update($data);
            Hook::add('menu_append_post',$this->plugins);
            $params = [
                'id' => $id
            ];
            Hook::listen('menu_append_post',$params,$this->ccc);
        }
        $caidanxiang = Db::name('nav')
            ->where('id', Request::instance()->get('c'))
            ->find();
        $zidingyi = '';
        if(substr($caidanxiang['href'],0,4) == 'http' || $this->doNothing($caidanxiang['href']))
        {
            $zidingyi = $caidanxiang['href'];
        }
        elseif(strpos($caidanxiang['href'],'#') !== false){
            $zidingyi = strstr($caidanxiang['href'],'#');
            $caidanxiang['href'] = str_replace($zidingyi,'',$caidanxiang['href']);
        }
        $this->assign('zidingyi', $zidingyi);
        $caidanxiang['icon'] = str_replace('"','&#34;',$caidanxiang['icon']);
        $this->assign('cdxiang', $caidanxiang);
        $this->addModifyMenu($caidanxiang['cid']);
        $this->menuAppend($caidanxiang['id']);
        $this->assign('backstageMenu', 'caidan');
        $this->assign('option', 'managemenu');
        return $this->view();
    }
    private function addModifyMenu($cid)
    {
        $cdfenlei = Db::name('nav_cat')->field('navcid,nav_name')->order('active desc')->select();
        $this->assign('cdfenlei', $cdfenlei);
        $caidan = Db::name('nav')->where('status', 1)->where('cid', $cid)->field('id,parent_id,label')->order('listorder')->select();
        if(is_array($caidan) && count($caidan) > 0)
        {
            $caidan = Tree::makeTreeForHtml($caidan);
            foreach($caidan as $key => $val){
                $caidan[$key]['level'] = str_repeat('&#12288;',$val['level']);
            }
        }
        $this->assign('caidan', $caidan);
        $yemian = Db::name('posts')->where('post_type', '1')->where('status', '1')->field('id,post_title,parent_id')->select();
        if(is_array($yemian) && count($yemian) > 0)
        {
            $yemian = Tree::makeTreeForHtml($yemian);
            foreach($yemian as $key => $val){
                $yemian[$key]['level'] = str_repeat('&#12288;',$val['level']);
            }
        }
        $this->assign('yemian', $yemian);
        $this->assign('fenlei', $this->getfenlei());
    }
    public function managemenu()
    {
        $this->checkUser();
        $this->checkPermissions(3);
        if(Request::instance()->has('paixu','post'))
        {
            $paixu = Request::instance()->post();
            foreach($paixu as $key => $val)
            {
                if($val != 'paixu')
                {
                    Db::name('nav')
                        ->where('id', $key)
                        ->update(['listorder' => intval($val)]);
                }
            }
        }
        if(Request::instance()->has('d','get'))
        {
            Db::name('nav')->where('id',Request::instance()->get('d'))->delete();
            Db::name('nav')
                ->where('parent_id', Request::instance()->get('d'))
                ->update(['parent_id' => Request::instance()->get('f')]);
        }
        $cdfenlei = Db::name('nav_cat')->field('navcid,nav_name')->order('active desc')->select();
        $this->assign('cdfenlei', $cdfenlei);
        $cid = 0;
        if(Request::instance()->has('caidanfenlei','post'))
        {
            $cid = Request::instance()->post('caidanfenlei');
        }
        elseif(Request::instance()->has('cid','get'))
        {
            $cid = Request::instance()->get('cid');
        }
        else
        {
            $nav = Db::name('nav_cat')->field('navcid')->order('active desc')->limit(1)->select();
            $cid = $nav[0]['navcid'];
        }
        $this->assign('cid', $cid);
        $data = Db::name('nav')->where('cid',$cid)->field('id,parent_id,label,status,listorder')->order('listorder')->select();
        if(is_array($data) && count($data) > 0)
        {
            $data = Tree::makeTreeForHtml($data);
            foreach($data as $key => $val){
                $data[$key]['level'] = str_repeat('&#12288;',$val['level']);
            }
        }
        $this->assign('data', $data);
        $this->assign('backstageMenu', 'caidan');
        $this->assign('option', 'managemenu');
        return $this->view();
    }
    public function web()
    {
        $this->checkUser();
        $this->checkPermissions(3);
        if(Request::instance()->has('title','post'))
        {
            $rule = [
                'email' => 'email',
                'domain' => 'require|regex:http(s)?:\/\/[\w\-:\.\/]+\/$'
            ];
            $msg = [
                'email.email' => Lang::get('The e-mail format is incorrect'),
                'domain.require' => Lang::get('Site domain name must be filled out'),
                'domain.regex' => Lang::get('Invalid domain name')
            ];
            $data = [
                'email' => Request::instance()->post('email'),
                'domain' => Request::instance()->post('domain')
            ];
            $validate = new Validate($rule, $msg);
            if(!$validate->check($data))
            {
                $this->error($validate->getError());
                return false;
            }
            $pinglun = 0;
            if(Request::instance()->has('pinglun','post') && Request::instance()->post('pinglun') == 'on')
            {
                $pinglun = 1;
            }
            $yanzheng = 0;
            if(Request::instance()->has('yanzheng','post') && Request::instance()->post('yanzheng') == 'on')
            {
                $yanzheng = 1;
            }
            $spare = [];
            $options_spare = Db::name('options')->where('option_name','spare')->field('option_value')->find();
            $options_spare = $options_spare['option_value'];
            if(!empty($options_spare))
            {
                $spare = unserialize($options_spare);
            }
            $rewrite = 0;
            if(Request::instance()->has('rewrite','post') && Request::instance()->post('rewrite') == 'on')
            {
                $rewrite = 1;
            }
            $spare['rewrite'] = $rewrite;
            $includeSubcategories = 0;
            if(Request::instance()->has('includeSubcategories','post') && Request::instance()->post('includeSubcategories') == 'on')
            {
                $includeSubcategories = 1;
            }
            $spare['includeSubcategories'] = $includeSubcategories;
            $notAllowLogin = 0;
            if(Request::instance()->has('notAllowLogin','post') && Request::instance()->post('notAllowLogin') == 'on')
            {
                $notAllowLogin = 1;
            }
            $spare['notAllowLogin'] = $notAllowLogin;
            $closeSlide = 1;
            if(Request::instance()->has('closeSlide','post') && Request::instance()->post('closeSlide') == 'on')
            {
                $closeSlide = 0;
            }
            $spare['closeSlide'] = $closeSlide;
            $openMessage = 0;
            if(Request::instance()->has('openMessage','post') && Request::instance()->post('openMessage') == 'on')
            {
                $openMessage = 1;
            }
            $spare['openMessage'] = $openMessage;
            $closeComment = 0;
            if(Request::instance()->has('closeComment','post') && Request::instance()->post('closeComment') == 'on')
            {
                $closeComment = 1;
            }
            $spare['closeComment'] = $closeComment;
            $datu = 0;
            if(Request::instance()->has('datu','post') && Request::instance()->post('datu') == 'on')
            {
                $datu = 1;
            }
            $spare['datu'] = $datu;
            $gudingbi = 0;
            if(Request::instance()->has('gudingbi','post') && Request::instance()->post('gudingbi') == 'on')
            {
                $gudingbi = 1;
            }
            $spare['gudingbi'] = $gudingbi;
            $kuanbi = Request::instance()->post('kuanbi');
            $kuanbi = abs(intval($kuanbi));
            if($kuanbi == 0)
            {
                $kuanbi = 4;
            }
            $spare['kuanbi'] = $kuanbi;
            $gaobi = Request::instance()->post('gaobi');
            $gaobi = abs(intval($gaobi));
            if($gaobi == 0)
            {
                $gaobi = 3;
            }
            $spare['gaobi'] = $gaobi;
            $homeShows = 10;
            if(Request::instance()->has('homeShows','post'))
            {
                $homeShows = intval(Request::instance()->post('homeShows'));
                if($homeShows < 1)
                {
                    $homeShows = 10;
                }
            }
            $spare['homeShows'] = $homeShows;
            $everyPageShows = 10;
            if(Request::instance()->has('everyPageShows','post'))
            {
                $everyPageShows = intval(Request::instance()->post('everyPageShows'));
                if($everyPageShows < 1)
                {
                    $everyPageShows = 10;
                }
            }
            $spare['everyPageShows'] = $everyPageShows;
            $ico = '';
            if(Request::instance()->has('ico','post'))
            {
                $ico = Request::instance()->post('ico');
            }
            $spare['ico'] = $ico;
            $tFormat = 'Y-m-d H:i:s';
            if(Request::instance()->has('timeFormat','post'))
            {
                $tFormat = Request::instance()->post('timeFormat');
            }
            $spare['timeFormat'] = $tFormat;
            $guanbi = 0;
            if(Request::instance()->has('guanbi','post') && Request::instance()->post('guanbi') == 'on')
            {
                $guanbi = 1;
            }
            $spare['guanbi'] = $guanbi;
            $closeSitemap = 0;
            if(Request::instance()->has('closeSitemap','post') && Request::instance()->post('closeSitemap') == 'on')
            {
                $closeSitemap = 1;
            }
            $spare['closeSitemap'] = $closeSitemap;
            $yincang = 0;
            if(Request::instance()->has('yincang','post') && Request::instance()->post('yincang') == 'on')
            {
                $yincang = 1;
            }
            $spare['yincang'] = $yincang;
            $retpwd = 0;
            if(Request::instance()->has('retpwd','post') && Request::instance()->post('retpwd') == 'on')
            {
                $retpwd = 1;
            }
            $spare['retpwd'] = $retpwd;
            $spare = serialize($spare);
            Db::name('options')
                ->where('option_name', 'title')
                ->update(['option_value' => htmlspecialchars(Request::instance()->post('title'))]);
            Db::name('options')
                ->where('option_name', 'subtitle')
                ->update(['option_value' => htmlspecialchars(Request::instance()->post('subtitle'))]);
            Db::name('options')
                ->where('option_name', 'keyword')
                ->update(['option_value' => htmlspecialchars(Request::instance()->post('keyword'))]);
            Db::name('options')
                ->where('option_name', 'description')
                ->update(['option_value' => htmlspecialchars(Request::instance()->post('description'))]);
            Db::name('options')
                ->where('option_name', 'email')
                ->update(['option_value' => Request::instance()->post('email')]);
            Db::name('options')
                ->where('option_name', 'template')
                ->update(['option_value' => Request::instance()->post('template')]);
            Db::name('options')
                ->where('option_name', 'comment')
                ->update(['option_value' => $pinglun]);
            Db::name('options')
                ->where('option_name', 'filter')
                ->update(['option_value' => Request::instance()->post('guolv')]);
            Db::name('options')
                ->where('option_name', 'record')
                ->update(['option_value' => Request::instance()->post('record')]);
            Db::name('options')
                ->where('option_name', 'copyright')
                ->update(['option_value' => serialize(Request::instance()->post('copyright'))]);
            Db::name('options')
                ->where('option_name', 'statistics')
                ->update(['option_value' => serialize(Request::instance()->post('statistics'))]);
            Db::name('options')
                ->where('option_name', 'slideshowWidth')
                ->update(['option_value' => Request::instance()->post('kuan')]);
            Db::name('options')
                ->where('option_name', 'slideshowHeight')
                ->update(['option_value' => Request::instance()->post('gao')]);
            Db::name('options')
                ->where('option_name', 'domain')
                ->update(['option_value' => Request::instance()->post('domain')]);
            Db::name('options')
                ->where('option_name', 'logo')
                ->update(['option_value' => Request::instance()->post('tubiao')]);
            Db::name('options')
                ->where('option_name', 'captcha')
                ->update(['option_value' => $yanzheng]);
            Db::name('options')
                ->where('option_name', 'spare')
                ->update(['option_value' => $spare]);
            Cache::clear();
        }
        $dir = glob(APP_PATH.'../public/*',GLOB_ONLYDIR);
        foreach($dir as $key => $val)
        {
            $tmpdir = basename($val);
            if($tmpdir == 'common' || $tmpdir == 'data' || substr($tmpdir,0,5) != 'cBlog')
            {
                unset($dir[$key]);
            }
            else
            {
                $dir[$key] = $tmpdir;
            }
        }
        $this->assign('dir', $dir);
        $siteInfo = Db::name('options')
            ->where('option_id','<','21')
            ->field('option_name,option_value')->select();
        $data = [];
        foreach($siteInfo as $key => $val){
            if($val['option_name'] == 'copyright' || $val['option_name'] == 'statistics')
            {
                $data[$val['option_name']] = unserialize($val['option_value']);
            }
            else if($val['option_name'] == 'spare')
            {
                if(!empty($val['option_value']))
                {
                    $spare = unserialize($val['option_value']);
                    foreach($spare as $skey => $sval)
                    {
                        $data[$skey] = $sval;
                    }
                }
            }
            else
            {
                $data[$val['option_name']] = $val['option_value'];
            }
        }
        if(!isset($data['rewrite']) && $this->is_rewrite() == true)
        {
            $data['rewrite'] = 1;
        }
        if(!isset($data['closeSlide']))
        {
            $data['closeSlide'] = 1;
        }
        $data['record'] = str_replace('"','\'',$data['record']);
        $this->assign('data', $data);
        $now = time();
        $timeFormat = [];
        $timeFormat[] = [
            'val' => 'Y-m-d H:i:s',
            'show' => date('Y-m-d H:i:s',$now)
        ];
        $timeFormat[] = [
            'val' => 'Y年m月d日 H'.Lang::get('点').'i分s秒',
            'show' => date('Y年m月d日 H'.Lang::get('点').'i分s秒',$now)
        ];
        $timeFormat[] = [
            'val' => 'Y年m月d日 H:i:s',
            'show' => date('Y年m月d日 H:i:s',$now)
        ];
        $timeFormat[] = [
            'val' => 'Y/m/d H:i:s',
            'show' => date('Y/m/d H:i:s',$now)
        ];
        $timeFormat[] = [
            'val' => 'Y.m.d H:i:s',
            'show' => date('Y.m.d H:i:s',$now)
        ];
        $timeFormat[] = [
            'val' => 'M d Y h:i:s A',
            'show' => date('M d Y h:i:s A',$now)
        ];
        $timeFormat[] = [
            'val' => 'F d Y h:i:s A',
            'show' => date('F d Y h:i:s A',$now)
        ];
        $timeFormat[] = [
            'val' => 'Y-m-d',
            'show' => date('Y-m-d',$now)
        ];
        $timeFormat[] = [
            'val' => 'Y年m月d日',
            'show' => date('Y年m月d日',$now)
        ];
        $timeFormat[] = [
            'val' => 'Y/m/d',
            'show' => date('Y/m/d',$now)
        ];
        $timeFormat[] = [
            'val' => 'Y.m.d',
            'show' => date('Y.m.d',$now)
        ];
        $timeFormat[] = [
            'val' => 'M d Y',
            'show' => date('M d Y',$now)
        ];
        $timeFormat[] = [
            'val' => 'F d Y',
            'show' => date('F d Y',$now)
        ];
        $this->assign('timeFormat', $timeFormat);
        $this->assign('backstageMenu', 'xitong');
        $this->assign('option', 'web');
        return $this->view();
    }
    public function themes()
    {
        $this->checkUser();
        $this->checkPermissions(3);
        $dqzhuti = Db::name('options')->where('option_name','template')->field('option_value')->find();
        $dqzhuti = $dqzhuti['option_value'];
        if(Request::instance()->has('themeName','post'))
        {
            if(is_file(ROOT_PATH.'public/'.$dqzhuti.'/'.ucfirst($dqzhuti).'.php'))
            {
                $params = [
                    'theme' => $dqzhuti
                ];
                Hook::add('theme_close','theme\\'.$dqzhuti.'\\'.ucfirst($dqzhuti));
                Hook::listen('theme_close',$params,$this->ccc);
            }
            $themeName = Request::instance()->post('themeName');
            Db::name('options')
                ->where('option_name', 'template')
                ->update(['option_value' => $themeName]);
            $dqzhuti = $themeName;
            if(is_file(ROOT_PATH.'public/'.$dqzhuti.'/'.ucfirst($dqzhuti).'.php')){
                $params = [
                    'theme' => $dqzhuti
                ];
                Hook::add('theme_open','theme\\'.$dqzhuti.'\\'.ucfirst($dqzhuti));
                Hook::listen('theme_open',$params,$this->ccc);
                Hook::add('open_theme_setting','theme\\'.$dqzhuti.'\\'.ucfirst($dqzhuti));
                $params = [
                    'open' => 0
                ];
                Hook::listen('open_theme_setting',$params,$this->ccc);
                if($params['open'] === 1 || $params['open'] == 'open')
                {
                    $this->assign('openThemeSetting', 1);
                }
                else
                {
                    $this->assign('openThemeSetting', 0);
                }
            }
            else
            {
                $this->assign('openThemeSetting', 0);
            }
            Cache::clear();
        }
        $themes = [];
        $domain = $this->host();
        $dir = glob(APP_PATH.'../public/*',GLOB_ONLYDIR);
        foreach($dir as $key => $val)
        {
            $tmpdir = basename($val);
            if($tmpdir != 'common' && $tmpdir != 'data' && substr($tmpdir,0,5) == 'cBlog')
            {
                $url = $domain.'public/common/images/screenshot.jpg';
                $path = APP_PATH.'../public/'.$tmpdir.'/screenshot.jpg';
                if(is_file($path))
                {
                    $url = $domain.'public/'.$tmpdir.'/screenshot.jpg';
                }
                $dq = 0;
                if($dqzhuti == $tmpdir)
                {
                    $dq = 1;
                }
                if($dq == 1)
                {
                    array_unshift($themes,[
                        'name' => $tmpdir,
                        'url' => $url,
                        'open' => $dq
                    ]);
                }
                else
                {
                    array_push($themes,[
                        'name' => $tmpdir,
                        'url' => $url,
                        'open' => $dq
                    ]);
                }
            }
        }
        $this->assign('themes', $themes);
        $this->assign('backstageMenu', 'xitong');
        $this->assign('option', 'themes');
        return $this->view();
    }
    public function themesetting()
    {
        $this->checkUser();
        $this->checkPermissions(3);
        if(is_file(ROOT_PATH.'public/'.$this->template.'/setting/'.$this->lang.'.php'))
        {
            Lang::load(ROOT_PATH . 'public/'.$this->template.'/setting/'.$this->lang.'.php');
        }
        if(is_file(ROOT_PATH.'public/'.$this->template.'/'.ucfirst($this->template).'.php'))
        {
            Hook::add('theme_setting_post','theme\\'.$this->template.'\\'.ucfirst($this->template));
            $params = [];
            if(Request::instance()->isPost())
            {
                Hook::listen('theme_setting_post',$params,$this->ccc);
            }
            Hook::add('theme_setting','theme\\'.$this->template.'\\'.ucfirst($this->template));
            Hook::listen('theme_setting',$params,$this->ccc);
            if(!empty($params['view']))
            {
                $this->assign('themesetting', $params['view']);
            }
            else
            {
                $this->assign('themesetting', '');
            }
        }
        else
        {
            $this->assign('themesetting', '');
        }
        $this->assign('backstageMenu', 'xitong');
        $this->assign('option', 'themesetting');
        return $this->view();
    }
    public function personal()
    {
        $this->checkUser();
        $this->checkPermissions(7);
        if(Request::instance()->has('user_nicename','post'))
        {
            $rule = [
                'email' => 'require|email'
            ];
            $msg = [
                'email.require' => Lang::get('E-mail address is required'),
                'email.email' => Lang::get('The e-mail format is incorrect')
            ];
            $data = [
                'email' => Request::instance()->post('email')
            ];
            $validate = new Validate($rule, $msg);
            if(!$validate->check($data))
            {
                $this->error($validate->getError());
                return false;
            }
            $avatar = Db::name('users')
                ->where('id', Session::get($this->session_prefix.'user_id'))
                ->field('avatar')
                ->find();
            $ava = $avatar['avatar'];
            if($this->isLegalPicture(Request::instance()->post('avatar')))
            {
                $ava = Request::instance()->post('avatar');
            }
            $xingbie = Request::instance()->post('sex');
            $birthday = htmlspecialchars(Request::instance()->post('birthday'));
            if(empty($birthday))
            {
                $birthday = null;
            }
            $signature = [
                'xuexiao' => htmlspecialchars(Request::instance()->post('xuexiao')),
                'qq' => htmlspecialchars(Request::instance()->post('qq')),
                'weibo' => htmlspecialchars(Request::instance()->post('weibo')),
                'weixin' => htmlspecialchars(Request::instance()->post('weixin')),
                'facebook' => htmlspecialchars(Request::instance()->post('facebook')),
                'twitter' => htmlspecialchars(Request::instance()->post('twitter')),
                'skype' => htmlspecialchars(Request::instance()->post('skype')),
                'signature' => htmlspecialchars(Request::instance()->post('signature'))
            ];
            $signature = serialize($signature);
            $pdata = [
                'user_nicename' => htmlspecialchars(Request::instance()->post('user_nicename')),
                'user_email' => htmlspecialchars(Request::instance()->post('email')),
                'sex' => intval($xingbie),
                'birthday' => $birthday,
                'user_url' => htmlspecialchars(Request::instance()->post('user_url')),
                'signature' => $signature,
                'avatar' => $ava,
                'mobile' => htmlspecialchars(Request::instance()->post('mobile'))
            ];
            Db::name('users')
                ->where('id', Session::get($this->session_prefix.'user_id'))
                ->update($pdata);
        }
        $data = Db::name('users')
            ->where('id',Session::get($this->session_prefix.'user_id'))
            ->field('user_nicename,user_email,user_url,avatar,sex,birthday,signature,mobile')
            ->find();
        $xarr = ['xuexiao','qq','weibo','weixin','facebook','twitter','skype'];
        if($this->is_serialize_array($data['signature']))
        {
            $tmparr = unserialize($data['signature']);
            foreach($xarr as $val)
            {
                if(isset($tmparr[$val]))
                {
                    $data[$val] = $tmparr[$val];
                }
            }
            $data['signature'] = $tmparr['signature'];
        }
        foreach($xarr as $val)
        {
            if(!isset($data[$val]))
            {
                $data[$val] = '';
            }
        }
        $this->assign('data', $data);
        $this->assign('backstageMenu', 'yonghu');
        $this->assign('option', 'personal');
        return $this->view();
    }
    public function uploadhead()
    {
        $this->checkPermissions(7);
        $file = request()->file('file');
        $validate = [
            'ext' => 'jpg,png,gif,jpeg'
        ];
        $file->validate($validate);
        $info = $file->move(ROOT_PATH . 'data' . DS . 'uploads');
        if($info){
            $image = \think\Image::open(ROOT_PATH . 'data' . DS . 'uploads' . DS . $info->getSaveName());
            $width = $image->width();
            $height = $image->height();
            if($width > $height){
                $width = $height;
            }
            if($width > 200){
                $width = 200;
            }
            @$image->thumb($width,$width,\think\Image::THUMB_CENTER)->save(ROOT_PATH . 'data' . DS . 'uploads' . DS . $info->getSaveName());
            echo str_replace('\\','/',$info->getSaveName());
        }
        else{
            echo $file->getError();
        }
        exit();
    }
    public function change()
    {
        $this->checkUser();
        $this->checkPermissions(7);
        if(Request::instance()->has('oldPassword','post'))
        {
            $rule = [
                'oldPassword' => 'require',
                'newPassword' => 'require|min:8',
                'repeat' => 'require'
            ];
            $msg = [
                'oldPassword.require' => Lang::get('The original password must be filled in'),
                'newPassword.require' => Lang::get('The new password must be filled in'),
                'newPassword.min' => Lang::get('The new password can not be shorter than 8 characters'),
                'repeat.require' => Lang::get('Confirm the new password must be filled out')
            ];
            $data = [
                'oldPassword' => Request::instance()->post('oldPassword'),
                'newPassword' => Request::instance()->post('newPassword'),
                'repeat' => Request::instance()->post('repeat')
            ];
            $validate = new Validate($rule, $msg);
            if(!$validate->check($data))
            {
                $this->error($validate->getError());
                return false;
            }
            if(Request::instance()->post('newPassword') != Request::instance()->post('repeat'))
            {
                $this->error(Lang::get('Confirm that the new password and the new password do not match'));
                return false;
            }
            $data = Db::name('users')
                ->where('id', Session::get($this->session_prefix.'user_id'))
                ->field('user_pass')
                ->find();
            if($data['user_pass'] != md5(Request::instance()->post('oldPassword')))
            {
                $this->error(Lang::get('The original password is wrong'));
                return false;
            }
            else
            {
                Db::name('users')
                    ->where('id', Session::get($this->session_prefix.'user_id'))
                    ->update(['user_pass' => md5(Request::instance()->post('newPassword'))]);
            }
        }
        $this->assign('backstageMenu', 'yonghu');
        $this->assign('option', 'change');
        return $this->view();
    }
    public function version()
    {
        $this->checkPermissions(6);
        $versionCache = Cache::get('latestVersion');
        $version = Config::get('version');
        $version_number = trim(substr(trim($version['number']),1));
        $versionCache_number = '';
        if($versionCache != false)
        {
            $versionCache_number = trim(substr(trim($versionCache),1));
        }
        if($versionCache == false || version_compare($version_number, $versionCache_number) >= 0)
        {
            $res = $this->getVersion($version['official']);
            if(strtoupper(substr($res,0,1)) == 'V')
            {
                Cache::set('latestVersion',$res,43200);
                return $res;
            }
        }
        else
        {
            return $versionCache;
        }
        return Lang::get('Did not find the latest version information');
    }
    public function dbbackup()
    {
        $this->checkUser();
        $this->checkPermissions(3);
        if(Request::instance()->has('dbbackup','post'))
        {
            ini_set('max_execution_time', 0);
            ini_set('memory_limit', -1);
            $dbnm = Config::get('database.database');
            $dbPrefix = Config::get('database.prefix');
            $prefixlen = strlen($dbPrefix);
            $bkstr = '';
            $sql = "SHOW TABLES FROM {$dbnm} LIKE '{$dbPrefix}%'";
            $renm = Db::query($sql);
            foreach($renm as $nmval){
                reset($nmval);
                $tbnm = current($nmval);
                $onlynm = substr($tbnm, $prefixlen);
                $sql = 'SHOW COLUMNS FROM `'.$tbnm.'`';
                $re = Db::query($sql);
                $field = '';
                foreach($re as $val){
                    if(empty($field)){
                        $field = '`'.$val['Field'].'`';
                    }
                    else{
                        $field .= ', `'.$val['Field'].'`';
                    }
                }
                $tmp = '';
                $all = Db::name($onlynm)->select();
                if(is_array($all) && count($all) > 0){
                    $i = 0;
                    foreach((array)$all as $rec){
                        $str = '';
                        foreach($rec as $key => $srec){
                            if(empty($str)){
                                $str = $this->strint($srec);
                            }
                            else{
                                $str .= ', '.$this->strint($srec);
                            }
                        }
                        if(empty($tmp)){
                            $tmp .= '('.$str.')';
                        }
                        else{
                            $tmp .= ',('.$str.')';
                        }
                        $i ++ ;
                        if($i > 50){
                            $this->semiinsert($tbnm, $field, $tmp, $bkstr);
                            $tmp = '';
                            $i = 0;
                        }
                    }
                    if(!empty($tmp)){
                        $this->semiinsert($tbnm, $field, $tmp, $bkstr);
                    }
                }
            }
            $bkstr = '-- 鲶鱼BLOG数据库备份' . PHP_EOL . '-- 生成日期：' . date('Y-m-d H: i: s') . PHP_EOL . '-- Table prefix: ' . $dbPrefix . PHP_EOL . $bkstr;
            $bkpath = date('Ymd');
            $bkname = date('Y-m-d_H-i-s');
            $bk = ROOT_PATH . 'data' . DS . 'cdbbackup' . DS . $bkpath;
            if(!is_dir($bk)){
                mkdir($bk, 0777, true);
            }
            $sqlf = $bkname.'.cbb';
            file_put_contents($bk.DS.$sqlf, gzcompress($bkstr));
            $dbrec = $this->getb('cdbbackup');
            $recpath = $bkpath . '/' . $sqlf;
            if(empty($dbrec)){
                $dbrec = $recpath;
            }
            else{
                if(strpos($dbrec,$recpath) === false){
                    $dbrec .= ','.$recpath;
                }
            }
            $this->setb('cdbbackup', $dbrec);
        }
        $this->assign('cdbbackup',$this->showdbbackup());
        $this->assign('backstageMenu', 'xitong');
        $this->assign('option', 'dbbackup');
        return $this->view();
    }
    public function deldbbackup()
    {
        $this->checkPermissions(3);
        $fn = Request::instance()->post('fn');
        $dbrec = ',' . $this->getb('cdbbackup');
        $dbrec = str_replace(',' . $fn, '', $dbrec);
        $dbrec = empty($dbrec) ? '' : substr($dbrec, 1);
        $this->setb('cdbbackup', $dbrec);
        @unlink(ROOT_PATH . str_replace('/', DS, 'data/cdbbackup/' . $fn));
        echo 'ok';
        exit();
    }
    public function redbbackup()
    {
        $this->checkPermissions(3);
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', -1);
        $file = ROOT_PATH . 'data' . DS . 'cdbbackup' . DS . str_replace('/', DS, Request::instance()->post('fn'));
        echo $this->restoredb($file, Config::get('database.database'), Config::get('database.prefix'));
        exit();
    }
    public function uploadrestore()
    {
        $this->checkUser();
        $this->checkPermissions(3);
        $prompt = '';
        if(Request::instance()->has('uploadrestore','post'))
        {
            ini_set('max_execution_time', 0);
            ini_set('memory_limit', -1);
            $file = request()->file('file');
            if($file->checkExt('cbb') === true){
                $rem  = $this->restoredb($file->getPathname(), Config::get('database.database'), Config::get('database.prefix'));
                if($rem == 'ok'){
                    $prompt = Lang::get('The database has been restored');
                }
                else{
                    $prompt = $rem;
                }
            }
            else{
                $prompt = Lang::get('Please select the correct backup file');
            }
        }
        $this->assign('cdbbackup',$this->showdbbackup());
        $this->assign('prompt',$prompt);
        $this->assign('backstageMenu', 'xitong');
        $this->assign('option', 'dbbackup');
        return $this->view('dbbackup');
    }
    public function clearcache()
    {
        $this->checkUser();
        $this->checkPermissions(5);
        if(Request::instance()->has('clearcache','post'))
        {
            Cache::clear();
        }
        $this->assign('backstageMenu', 'xitong');
        $this->assign('option', 'clearcache');
        return $this->view();
    }
    public function author()
    {
        $this->checkUser();
        $this->checkPermissions(1);
        if(Request::instance()->has('shouquanma','post'))
        {
            $rule = [
                'shouquanma' => 'require|min:40'
            ];
            $msg = [
                'shouquanma.require' => Lang::get('Authorization code must be filled in'),
                'shouquanma.min' => Lang::get('Authorization code error')
            ];
            $data = [
                'shouquanma' => Request::instance()->post('shouquanma')
            ];
            $validate = new Validate($rule, $msg);
            if(!$validate->check($data))
            {
                $this->error($validate->getError());
                return false;
            }
            $url = 'http://www.catfish-cms.com/author.html?act=author&dm='.urlencode($_SERVER['HTTP_HOST']).'&pw=Nzc5ZTE3M2Q3NWM0N2VkNzdjZDk4NjgyZTgzODgyOTM%3D&author='.trim($data['shouquanma']);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 2.0.50727;http://www.baidu.com)');
            curl_setopt($ch , CURLOPT_URL , $url);
            $res = curl_exec($ch);
            curl_close($ch);
            if(strlen($res) > 3)
            {
                $author = $this->getb('author');
                if(!empty($author))
                {
                    $author = unserialize($author);
                    $author['veri'] = 1;
                    $author['rm'] = $res;
                    $author['sq'] = trim($data['shouquanma']);
                    $author['ck'] = md5($author['rm'].$author['sq']);
                    $this->setb('author',serialize($author));
                }
            }
        }
        $author = $this->getb('author');
        $veri = 0;
        $rm = [];
        $sq = '';
        $ts = '';
        if(!empty($author))
        {
            $author = unserialize($author);
            $veri = $author['veri'];
            if(isset($author['sq']))
            {
                $sq = $author['sq'];
            }
            if(isset($author['rm']))
            {
                if($author['ck'] != md5($author['rm'].$author['sq']))
                {
                    $this->redirect(Url::build('/error'));
                    exit();
                }
                $rm = unserialize(base64_decode($author['rm']));
                if(!isset($rm['url']) || !isset($rm['lt']) || !isset($rm['end']) || stripos($_SERVER['HTTP_HOST'],$rm['url']) === false)
                {
                    $this->redirect(Url::build('/error'));
                    exit();
                }
                else
                {
                    if($rm['lt'] != 1)
                    {
                        if(strtotime("+1 month") > strtotime($rm['end']))
                        {
                            $ts = Lang::get('Your authorization deadline is about to expire. In order not to affect your normal use, please renew your license in time.');
                        }
                        elseif(time() > strtotime($rm['end']))
                        {
                            $ts = Lang::get('Your license period has expired. In order not to affect your normal use, please renew the license immediately.');
                        }
                    }
                }
            }
        }
        else
        {
            $this->redirect(Url::build('/error'));
            exit();
        }
        $this->assign('veri', $veri);
        $this->assign('sq', $sq);
        $this->assign('rm', $rm);
        $this->assign('ts', $ts);
        $this->assign('backstageMenu', 'xitong');
        $this->assign('option', 'author');
        return $this->view();
    }
    public function shoucang()
    {
        $this->checkUser();
        $data = Db::name('user_favorites')->where('uid',Session::get($this->session_prefix.'user_id'))->order('createtime desc')->paginate(10);
        $this->assign('data', $data);
        $this->assign('root', $this->root());
        $this->assign('backstageMenu', 'yonghu');
        $this->assign('option', 'shoucang');
        return $this->view();
    }
    public function removeshoucang()
    {
        Db::name('user_favorites')->where('id',Request::instance()->post('id'))->where('uid',Session::get($this->session_prefix.'user_id'))->delete();
        return true;
    }
    public function pinglun()
    {
        $this->checkUser();
        $data = Db::name('comments')
            ->where('uid',Session::get($this->session_prefix.'user_id'))
            ->order('createtime desc')
            ->paginate(10);
        $this->assign('data', $data);
        $this->assign('root', $this->root());
        $this->assign('backstageMenu', 'yonghu');
        $this->assign('option', 'pinglun');
        return $this->view();
    }
    public function removepinglun()
    {
        Db::name('comments')->where('id',Request::instance()->post('id'))->where('uid',Session::get($this->session_prefix.'user_id'))->delete();
        return true;
    }
    public function plugin()
    {
        $this->checkUser();
        $this->checkPermissions(3);
        $plugins = Db::name('options')->where('option_name','plugins')->field('option_value')->find();
        if(!empty($plugins))
        {
            $plugins = unserialize($plugins['option_value']);
        }
        else
        {
            $plugins = [];
        }
        $data = [];
        $dir = glob(APP_PATH.'plugins/*',GLOB_ONLYDIR);
        foreach($dir as $key => $val)
        {
            $pluginName = basename($val);
            Lang::load(APP_PATH . 'plugins/'.$pluginName.'/lang/'.$this->lang.'.php');
            $readme = APP_PATH.'plugins/'.$pluginName.'/readme.txt';
            if(!is_file($readme))
            {
                $readme = APP_PATH.'plugins/'.$pluginName.'/'.ucfirst($pluginName).'.php';
            }
            if(!is_file($readme))
            {
                continue;
            }
            $pluginStr = file_get_contents($readme);
            $pName = '';
            if(preg_match("/(插件名|Plugin Name)\s*(：|:)(.*)/i", $pluginStr ,$matches))
            {
                $pName = trim($matches[3]);
            }
            $pluginDesc = '';
            if(preg_match("/(描述|Description)\s*(：|:)(.*)/i", $pluginStr ,$matches))
            {
                $pluginDesc = trim($matches[3]);
            }
            $pluginAuth = '';
            if(preg_match("/(作者|Author)\s*(：|:)(.*)/i", $pluginStr ,$matches))
            {
                $pluginAuth = trim($matches[3]);
            }
            $pluginVers = '';
            if(preg_match("/(版本|Version)\s*(：|:)(.*)/i", $pluginStr ,$matches))
            {
                $pluginVers = trim($matches[3]);
            }
            $pluginUri = '';
            if(preg_match("/(插件网址|插件網址|Plugin URI|Plugin URL)\s*(：|:)(.*)/i", $pluginStr ,$matches))
            {
                $pluginUri = trim($matches[3]);
            }
            $quanxian = 3;
            if(preg_match("/(权限|權限|Jurisdiction)\s*(：|:)(.*)/i", $pluginStr ,$matches))
            {
                $quanxian = intval($this->getPermission(trim($matches[3])));
                if($quanxian == 0)
                {
                    $quanxian = 1;
                }
            }
            $appliance = 'all';
            if(preg_match("/(适用|適用|Appliance)\s*(：|:)(.*)/i", $pluginStr ,$matches))
            {
                $appliance = strtolower(trim($matches[3]));
            }
            $data[] = [
                'plugin' => $pluginName,
                'name' => Lang::get($pName),
                'description' => Lang::get($pluginDesc),
                'author' => $pluginAuth,
                'version' => $pluginVers,
                'pluginUrl' => $pluginUri,
                'jurisdiction' => $quanxian,
                'appliance' => $appliance
            ];
        }
        $dataArr = [];
        foreach($data as $key => $val)
        {
            if(in_array($val['plugin'],$plugins))
            {
                $data[$key]['open'] = 1;
                $dataArr[] = $val['plugin'];
            }
            else
            {
                $data[$key]['open'] = 0;
            }
            if(Session::has($this->session_prefix.'user_type') && Session::get($this->session_prefix.'user_type') > $val['jurisdiction'])
            {
                unset($data[$key]);
            }
            if(strtolower($val['appliance']) != 'all' && strtolower($val['appliance']) != 'blog')
            {
                unset($data[$key]);
            }
        }
        $this->assign('data', $data);
        $intArr=array_intersect($plugins,$dataArr);
        if(count($intArr) < count($plugins))
        {
            Db::name('options')
                ->where('option_name', 'plugins')
                ->update(['option_value' => serialize($intArr)]);
        }
        $this->assign('backstageMenu', 'kuozhan');
        $this->assign('option', 'plugin');
        return $this->view();
    }
    public function pluginkaiguan()
    {
        $this->checkPermissions(3);
        $norecord = false;
        $plugins = Db::name('options')->where('option_name','plugins')->field('option_value')->find();
        if(!empty($plugins))
        {
            $plugins = unserialize($plugins['option_value']);
        }
        else
        {
            $norecord = true;
            $plugins = [];
        }
        $pluginame = Request::instance()->post('pn');
        $find = array_search($pluginame,$plugins);
        $params = [
            'plugin' => $pluginame
        ];
        if($find === false)
        {
            $pluginpath = APP_PATH.'plugins/'.$pluginame.'/'.ucfirst($pluginame).'.php';
            if(is_file($pluginpath))
            {
                $plugins[] = $pluginame;
                Hook::add('open','app\\plugins\\'.$pluginame.'\\'.ucfirst($pluginame));
                Hook::listen('open',$params,$this->ccc);
            }
        }
        else
        {
            unset($plugins[$find]);
            Hook::add('close','app\\plugins\\'.$pluginame.'\\'.ucfirst($pluginame));
            Hook::listen('close',$params,$this->ccc);
        }
        if($norecord == true)
        {
            $data = ['option_name' => 'plugins', 'option_value' => serialize($plugins), 'autoload' => 0];
            Db::name('options')->insert($data);
        }
        else
        {
            Db::name('options')
                ->where('option_name','plugins')
                ->update(['option_value' => serialize($plugins)]);
        }
        Cache::rm('plugins');
        Cache::rm('pluginslist');
        return true;
    }
    public function plugins($plugin)
    {
        $this->checkUser();
        $nofile = false;
        $readme = APP_PATH.'plugins/'.$plugin.'/readme.txt';
        if(!is_file($readme))
        {
            $readme = APP_PATH.'plugins/'.$plugin.'/'.ucfirst($plugin).'.php';
        }
        $pluginStr = @file_get_contents($readme);
        if($pluginStr === false)
        {
            $nofile = true;
        }
        $quanxian = 3;
        if($nofile == false && @preg_match("/(权限|權限|Jurisdiction)\s*(：|:)(.*)/i", $pluginStr ,$matches))
        {
            $quanxian = intval($this->getPermission(trim($matches[3])));
            if($quanxian == 0)
            {
                $quanxian = 1;
            }
        }
        $this->checkPermissions($quanxian);
        Hook::add('settings','app\\plugins\\'.$plugin.'\\'.ucfirst($plugin));
        Hook::add('settings_post','app\\plugins\\'.$plugin.'\\'.ucfirst($plugin));
        $params = [];
        if(Request::instance()->isPost())
        {
            Hook::listen('settings_post',$params,$this->ccc);
        }
        Hook::listen('settings',$params,$this->ccc);
        $pName = $plugin;
        if($nofile == false && @preg_match("/(插件名|Plugin Name)\s*(：|:)(.*)/i", $pluginStr ,$matches))
        {
            $pName = trim($matches[3]);
        }
        $this->assign('pluginName', $pName);
        if($nofile == true)
        {
            $this->assign('data', Lang::get('Plugin not found'));
        }
        else
        {
            if(!empty($params['view']))
            {
                $this->assign('data', $params['view']);
            }
            else
            {
                $this->assign('data', Lang::get('The plugin has no settings'));
            }
        }
        if($quanxian > 3 && $this->permissions > 3){
            $this->assign('backstageMenu', 'other');
        }
        else{
            $this->assign('backstageMenu', 'kuozhan');
        }
        $this->assign('option', $plugin);
        return $this->view();
    }
    public function detection()
    {
        $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
        $host = $_SERVER['HTTP_HOST'];
        $len = Request::instance()->isSsl() ? 8 : 7;
        if(substr($referer,$len,strlen($host)) == $host)
        {
            @ignore_user_abort(true);
            @set_time_limit(300);
            $wt = Db::name('options')->where('option_name','title')->field('option_value')->find();
            if(!empty($wt['option_value']))
            {
                $wt = $wt['option_value'];
            }
            else
            {
                $wt = '';
            }
            $version = Config::get('version');
            $ver = trim(substr($version['number'],1));
            $ch = curl_init();
            $url = 'http://www.'.$version['official'].'/_version/blogUpgrade/?t='.urlencode($version['catfishType']).'&v='.urlencode($ver).'&tl='.urlencode($wt).'&dm='.urlencode($_SERVER['HTTP_HOST'].Url::build('/'));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 2.0.50727;http://www.baidu.com)');
            curl_setopt($ch , CURLOPT_URL , $url);
            $res = curl_exec($ch);
            curl_close($ch);
            if(!empty($res))
            {
                $res = json_decode($res,true);
                foreach($res as $key => $val)
                {
                    $res[$key] = urldecode($val);
                }
                $kongzhi = $this->getb('newv');
                $time = time();
                if(!empty($kongzhi))
                {
                    $kongzhi = unserialize($kongzhi);
                    $kongzhi['time'] = $time + 86400;
                    $kongzhi['version'] = $res['version'];
                    $kongzhi['address'] = $res['address'];
                }
                else
                {
                    $kongzhi = [
                        'time' => $time,
                        'show' => $time,
                        'version' => $res['version'],
                        'address' => $res['address']
                    ];
                }
                $kongzhi = serialize($kongzhi);
                $this->setb('newv',$kongzhi);
                if(version_compare($ver,$res['version']) < 0)
                {
                    die('show');
                }
            }
        }
        exit();
    }
    public function aweek()
    {
        $kongzhi = $this->getb('newv');
        if(!empty($kongzhi))
        {
            $kongzhi = unserialize($kongzhi);
            $kongzhi['show'] = time() + 604800;
            $kongzhi = serialize($kongzhi);
            $this->setb('newv',$kongzhi);
        }
        exit();
    }
    public function upgrade()
    {
        $this->checkUser();
        $this->checkPermissions(3);
        $kongzhi = $this->getb('newv');
        $dizhi = '';
        if(!empty($kongzhi))
        {
            $kongzhi = unserialize($kongzhi);
            $addArr = explode(',',$kongzhi['address']);
            foreach($addArr as $val)
            {
                $dizhi .= '<a href="'.$val.'" target="_blank">'.$val.'</a><br><br>';
            }
        }
        if(empty($dizhi))
        {
            $dizhi = '<a href="//www.catfish-cms.com" target="_blank">'.Lang::get('Catfish Blog official website').'</a><br><br>';
        }
        $this->assign('address', $dizhi);
        $this->assign('backstageMenu', 'xitong');
        $this->assign('option', 'upgrade');
        return $this->view();
    }
    public function sysupgrade()
    {
        $this->checkPermissions(3);
        $file = request()->file('file');
        $validate = [
            'ext' => 'zip'
        ];
        $file->validate($validate);
        $tmp = ROOT_PATH . 'runtime' . DS . 'ctmp';
        if(!is_dir($tmp))
        {
            mkdir($tmp,0777,true);
        }
        $info = $file->rule('uniqid')->move($tmp);
        if($info){
            $fpath = $tmp . DS . $info->getSaveName();
            Cache::clear();
            $zip = new \ZipArchive();
            if($zip->open($fpath) === true)
            {
                $zip->extractTo(ROOT_PATH);
                $zip->close();
                $info = null;
                @unlink($fpath);
                $fpath = ROOT_PATH . 'catfishBlogUpgrade.html';
                if(is_file($fpath))
                {
                    @unlink($fpath);
                }
                $fpath = ROOT_PATH . 'catfishBlog.html';
                if(is_file($fpath))
                {
                    @unlink($fpath);
                }
            }
            $kongzhi = $this->getb('newv');
            if(!empty($kongzhi))
            {
                $kongzhi = unserialize($kongzhi);
                $kongzhi['time'] = time() + 604800;
                $kongzhi = serialize($kongzhi);
                $this->setb('newv',$kongzhi);
            }
            file_get_contents($this->host());
            echo 'ok';
        }
        else{
            echo 'fail';
        }
        exit();
    }
    public function uploadLogo()
    {
        $this->checkPermissions(3);
        $file = request()->file('file');
        $validate = [
            'ext' => 'jpg,png,gif,jpeg'
        ];
        $file->validate($validate);
        $info = $file->move(ROOT_PATH . 'data' . DS . 'uploads');
        if($info){
            echo str_replace('\\','/',$info->getSaveName());
        }
        else{
            echo $file->getError();
        }
        exit();
    }
    public function uploadIco()
    {
        $this->checkPermissions(3);
        $file = request()->file('file');
        $validate = [
            'ext' => 'ico'
        ];
        $file->validate($validate);
        $info = $file->move(ROOT_PATH . 'data' . DS . 'uploads');
        if($info){
            echo str_replace('\\','/',$info->getSaveName());
        }
        else{
            echo $file->getError();
        }
        exit();
    }
    public function upload()
    {
        $this->checkPermissions(6);
        $file = request()->file('file');
        $validate = [
            'ext' => 'jpg,png,gif,jpeg'
        ];
        $file->validate($validate);
        $info = $file->move(ROOT_PATH . 'data' . DS . 'uploads');
        if($info){
            $image = \think\Image::open(ROOT_PATH . 'data' . DS . 'uploads' . DS . $info->getSaveName());
            $width = $image->width();
            $height = $image->height();
            $options_spare = $this->optionsSpare();
            if(!isset($options_spare['datu']) || $options_spare['datu'] != 1)
            {
                $larger = str_replace('.','_larger.',$info->getSaveName());
                @$image->thumb(850, ($height * 850 / $width),\think\Image::THUMB_FIXED)->save(ROOT_PATH . 'data' . DS . 'uploads' . DS . $larger);
                $small = str_replace('.','_small.',$info->getSaveName());
                if(isset($options_spare['gudingbi']) && $options_spare['gudingbi'] == 1)
                {
                    @$image->thumb(470, ($options_spare['gaobi'] * 470 / $options_spare['kuanbi']),\think\Image::THUMB_CENTER)->save(ROOT_PATH . 'data' . DS . 'uploads' . DS . $small);
                }
                else
                {
                    @$image->thumb(470, ($height * 470 / $width),\think\Image::THUMB_FIXED)->save(ROOT_PATH . 'data' . DS . 'uploads' . DS . $small);
                }
            }
            if(isset($options_spare['gudingbi']) && $options_spare['gudingbi'] == 1)
            {
                @$image->thumb(350, ($options_spare['gaobi'] * 350 / $options_spare['kuanbi']),\think\Image::THUMB_CENTER)->save(ROOT_PATH . 'data' . DS . 'uploads' . DS . $info->getSaveName());
            }
            else
            {
                if($width > 350 || $height > 350)
                {
                    @$image->thumb(350, 350)->save(ROOT_PATH . 'data' . DS . 'uploads' . DS . $info->getSaveName());
                }
            }
            echo str_replace('\\','/',$info->getSaveName());
        }else{
            echo $file->getError();
        }
        exit();
    }
    public function uploadLinks()
    {
        $this->checkPermissions(3);
        $file = request()->file('file');
        $validate = [
            'ext' => 'jpg,png,gif,jpeg'
        ];
        $file->validate($validate);
        $info = $file->move(ROOT_PATH . 'data' . DS . 'uploads');
        if($info){
            if(Request::instance()->post('original') == 0){
                $image = \think\Image::open(ROOT_PATH . 'data' . DS . 'uploads' . DS . $info->getSaveName());
                $width = $image->width();
                $height = $image->height();
                if($width > 100 || $height > 50)
                {
                    @$image->thumb(100, 50, \think\Image::THUMB_FIXED)->save(ROOT_PATH . 'data' . DS . 'uploads' . DS . $info->getSaveName());
                }
            }
            echo str_replace('\\','/',$info->getSaveName());
        }else{
            echo $file->getError();
        }
        exit();
    }
    public function uploadSlideshow()
    {
        $this->checkPermissions(3);
        $file = request()->file('file');
        $validate = [
            'ext' => 'jpg,png,gif,jpeg'
        ];
        $file->validate($validate);
        $info = $file->move(ROOT_PATH . 'data' . DS . 'uploads');
        if($info){
            $slideshowWidth = Db::name('options')->where('option_name','slideshowWidth')->field('option_value')->find();
            $slideshowHeight = Db::name('options')->where('option_name','slideshowHeight')->field('option_value')->find();
            $image = \think\Image::open(ROOT_PATH . 'data' . DS . 'uploads' . DS . $info->getSaveName());
            @$image->thumb($slideshowWidth['option_value'],$slideshowHeight['option_value'],\think\Image::THUMB_FIXED)->save(ROOT_PATH . 'data' . DS . 'uploads' . DS . $info->getSaveName());
            echo str_replace('\\','/',$info->getSaveName());
        }else{
            echo $file->getError();
        }
        exit();
    }
    public function uploadCoverPic()
    {
        $this->checkPermissions(3);
        $file = request()->file('file');
        $validate = [
            'ext' => 'jpg,png,gif,jpeg'
        ];
        $file->validate($validate);
        $info = $file->move(ROOT_PATH . 'data' . DS . 'uploads');
        if($info){
            $image = \think\Image::open(ROOT_PATH . 'data' . DS . 'uploads' . DS . $info->getSaveName());
            @$image->thumb(1920, 600,\think\Image::THUMB_FIXED)->save(ROOT_PATH . 'data' . DS . 'uploads' . DS . $info->getSaveName());
            echo str_replace('\\','/',$info->getSaveName());
        }
        else{
            echo $file->getError();
        }
        exit();
    }
    public function uploadBgPic()
    {
        $this->checkPermissions(3);
        $file = request()->file('file');
        $validate = [
            'ext' => 'jpg,png,gif,jpeg'
        ];
        $file->validate($validate);
        $info = $file->move(ROOT_PATH . 'data' . DS . 'uploads');
        if($info){
            echo str_replace('\\','/',$info->getSaveName());
        }
        else{
            echo $file->getError();
        }
        exit();
    }
    private function getfenlei($fields = 'id,term_name,parent_id', $replace = '&nbsp;&nbsp;&nbsp;')
    {
        $data = Db::name('terms')->field($fields)->select();
        if(is_array($data) && count($data) > 0)
        {
            $r = Tree::makeTreeForHtml($data);
            foreach($r as $key => $val){
                $r[$key]['level'] = str_repeat($replace,$val['level']);
            }
            return $r;
        }
        else
        {
            return [];
        }
    }
    private function view($template = '')
    {
        $supportSlides = Cache::get('whether_support_slides');
        $supportLiuyan = Cache::get('whether_support_liuyan');
        if($supportSlides == false || $supportLiuyan == false)
        {
            $options_spare = Db::name('options')->where('option_name','spare')->field('option_value')->find();
            $supportSlides = $options_spare['option_value'];
            if(!empty($supportSlides))
            {
                $supportSlides = unserialize($supportSlides);
            }
            if(isset($supportSlides['openMessage']))
            {
                $supportLiuyan = $supportSlides['openMessage'];
            }
            else
            {
                $supportLiuyan = 0;
            }
            if(isset($supportSlides['closeSlide']))
            {
                $supportSlides = $supportSlides['closeSlide'];
            }
            else
            {
                $supportSlides = 1;
            }
            Cache::set('whether_support_slides',$supportSlides,3600);
            Cache::set('whether_support_liuyan',$supportLiuyan,3600);
        }
        $this->assign('closeSlide', $supportSlides);
        $this->assign('openMessage', $supportLiuyan);
        $sysupgrade = 0;
        $kongzhi = $this->getb('newv');
        if(!empty($kongzhi))
        {
            $kongzhi = unserialize($kongzhi);
            $version = Config::get('version');
            $version = trim(substr($version['number'],1));
            if(version_compare($version,$kongzhi['version']) < 0)
            {
                $sysupgrade = 1;
            }
        }
        $this->assign('sysupgrade', $sysupgrade);
        $version = $this->getConfig(Config::get('version'));
        $this->assign('catfish', '<a href="http://www.'.$version['official'].'/" target="_blank" id="catfish">'.$version['name'].'&nbsp;Blog&nbsp;'.$version['number'].'</a>&nbsp;&nbsp;');
        $this->assign('executionTime', Debug::getRangeTime('begin','end',4).'s');
        $qx = Cache::get('commqx');
        if($qx == false)
        {
            $qx = $this->getb('author');
            if(!empty($qx))
            {
                $qx = unserialize($qx);
            }
            Cache::set('commqx',$qx,3600);
        }
        if(isset($qx['open']))
        {
            $this->assign('auopen', $qx['open']);
        }
        else
        {
            $this->assign('auopen', 0);
        }
        $this->assign('domain', $this->host());
        $this->ptaoput($version);
        $view = $this->fetch($template);
        return $view;
    }
    private function root()
    {
        $root = '';
        $dm = Url::build('/');
        if(strpos($dm,'/index.php') !== false)
        {
            $root = 'index.php/';
        }
        return $root;
    }
    private function switchEditor()
    {
        Hook::add('switch_editor',$this->plugins);
        $editorParams = [
            'editor_css' => '',
            'editor_js' => '',
            'editor' => '',
            'js' => ''
        ];
        Hook::listen('switch_editor',$editorParams,$this->ccc);
        if(!empty($editorParams['editor_css']))
        {
            $this->assign('editor_css', $editorParams['editor_css']);
        }
        if(!empty($editorParams['editor_js']))
        {
            $this->assign('editor_js', $editorParams['editor_js']);
        }
        if(!empty($editorParams['editor']))
        {
            $this->assign('editor', $editorParams['editor']);
        }
        if(!empty($editorParams['js']))
        {
            $this->assign('js', $editorParams['js']);
        }
    }
    private function writeAlias($id)
    {
        Hook::add('write_alias',$this->plugins);
        $aliasParams = [
            'id' => $id,
            'view' => ''
        ];
        Hook::listen('write_alias',$aliasParams,$this->ccc);
        if(!empty($aliasParams['view']))
        {
            $this->assign('write_alias', $aliasParams['view']);
        }
    }
    private function writeAppend($id)
    {
        Hook::add('write_append',$this->plugins);
        $aliasParams = [
            'id' => $id,
            'view' => ''
        ];
        Hook::listen('write_append',$aliasParams,$this->ccc);
        if(!empty($aliasParams['view']))
        {
            $this->assign('write_append', $aliasParams['view']);
        }
    }
    private function writeAfterEditor($id)
    {
        Hook::add('write_after_editor',$this->plugins);
        $writeAfterParams = [
            'id' => $id,
            'view' => ''
        ];
        Hook::listen('write_after_editor',$writeAfterParams,$this->ccc);
        if(!empty($writeAfterParams['view']))
        {
            $this->assign('write_after_editor', $writeAfterParams['view']);
        }
    }
    private function writeRightAppend($id)
    {
        Hook::add('write_right_append',$this->plugins);
        $writeRight = [
            'id' => $id,
            'view' => ''
        ];
        Hook::listen('write_right_append',$writeRight,$this->ccc);
        if(!empty($writeRight['view']))
        {
            $this->assign('write_right_append', $writeRight['view']);
        }
    }
    public function bgPic()
    {
        $this->checkUser();
        $this->checkPermissions(3);
        if(Request::instance()->isPost())
        {
            $fengmian = Request::instance()->post('coverpic');
            $beijing = Request::instance()->post('backgroundpic');
            $ispic = true;
            if((!empty($fengmian) && !$this->isLegalPicture($fengmian)) || (!empty($beijing) && !$this->isLegalPicture($beijing)))
            {
                $ispic = false;
            }
            if($ispic == true)
            {
                $tu = [
                    'fengmian' => $fengmian,
                    'fengmianzi' => htmlspecialchars(Request::instance()->post('fengmianzi')),
                    'beijing' => $beijing
                ];
                $this->setb('bgPic',serialize($tu));
            }
        }
        $bgPic = $this->getb('bgPic');
        if(!empty($bgPic))
        {
            $bgPic = unserialize($bgPic);
        }
        else
        {
            $bgPic = [
                'fengmian' => '',
                'fengmianzi' => '',
                'beijing' => ''
            ];
        }
        $this->assign('fengmiantu', $bgPic['fengmian']);
        $this->assign('fengmianzi', $bgPic['fengmianzi']);
        $this->assign('beijingtu', $bgPic['beijing']);
        $this->assign('backstageMenu', 'yemian');
        $this->assign('option', 'bgPic');
        return $this->view();
    }
    public function delbgpic()
    {
        $this->checkUser();
        $this->checkPermissions(3);
        $pic = Request::instance()->post('pic');
        $w = stripos($pic,"/data/");
        $pic = substr($pic,$w);
        @unlink(APP_PATH.'..'.$pic);
    }
    public function uploadWrite()
    {
        $this->checkPermissions(6);
        $file = request()->file('file');
        $validate = [
            'ext' => 'jpg,png,gif,jpeg'
        ];
        $file->validate($validate);
        $info = $file->move(ROOT_PATH . 'data' . DS . 'uploads');
        if($info){
            echo $this->host().'data/uploads/'.str_replace('\\','/',$info->getSaveName());
        }
        else{
            echo $file->getError();
        }
        exit();
    }
    public function markdownUpload()
    {
        $this->checkPermissions(6);
        header('Content-type:text/json');
        $file = request()->file('editormd-image-file');
        $validate = [
            'ext' => 'jpg,png,gif,jpeg,webp'
        ];
        $file->validate($validate);
        $info = $file->move(ROOT_PATH . 'data' . DS . 'uploads');
        if($info){
            $rej = [
                'success' => 1,
                'message' => 'Ok',
                'url' => $this->host().'data/uploads/'.str_replace('\\','/',$info->getSaveName())
            ];
            echo json_encode($rej);
        }
        else{
            $rej = [
                'success' => 0,
                'message' => $file->getError(),
                'url' => ''
            ];
            echo json_encode($rej);
        }
        exit();
    }
    private function menuAppend($id)
    {
        Hook::add('menu_append',$this->plugins);
        $aliasParams = [
            'id' => $id,
            'view' => ''
        ];
        Hook::listen('menu_append',$aliasParams,$this->ccc);
        if(!empty($aliasParams['view']))
        {
            $this->assign('menu_append', $aliasParams['view']);
        }
    }
    public function common_post()
    {
        $out = '';
        Hook::add('common_post',$this->plugins);
        $params = [];
        Hook::listen('common_post',$params,$this->ccc);
        if(isset($params['view'])){
            $out = $params['view'];
        }
        return $out;
    }
    public function toutiao()
    {
        $this->setb('toutiao', Request::instance()->post('id'));
        Cache::rm('toutiao_front');
        echo 'ok';
        exit();
    }
    public function quxiaotoutiao()
    {
        $this->setb('toutiao', 0);
        Cache::rm('toutiao_front');
        echo 'ok';
        exit();
    }
    public function zhuyemian()
    {
        $this->setb('zhuyemian', Request::instance()->post('id'));
        Cache::rm('zhuyemian_front');
        echo 'ok';
        exit();
    }
    public function quxiaozhuyemian()
    {
        $this->setb('zhuyemian', 0);
        Cache::rm('zhuyemian_front');
        echo 'ok';
        exit();
    }
    public function smtpsettings()
    {
        $this->checkUser();
        $this->checkPermissions(1);
        if(Request::instance()->has('host','post'))
        {
            $rule = [
                'host' => 'require',
                'port' => 'require',
                'user' => 'require',
                'password' => 'require',
            ];
            $msg = [
                'host.require' => Lang::get('SMTP server address must be filled in'),
                'port.require' => Lang::get('Port number must be filled in'),
                'user.require' => Lang::get('Mailbox users must fill in'),
                'password.require' => Lang::get('Password must be filled in')
            ];
            $data = [
                'host' => Request::instance()->post('host'),
                'port' => Request::instance()->post('port'),
                'user' => Request::instance()->post('user'),
                'password' => Request::instance()->post('password')
            ];
            $validate = new Validate($rule, $msg);
            if(!$validate->check($data))
            {
                $this->error($validate->getError());
                return false;
            }
            $auth = 0;
            if(Request::instance()->has('auth','post') && Request::instance()->post('auth') == 'on')
            {
                $auth = 1;
            }
            $estis = serialize([
                'host' => $data['host'],
                'port' => $data['port'],
                'user' => $data['user'],
                'password' => $data['password'],
                'secure' => Request::instance()->post('secure'),
                'auth' => $auth
            ]);
            $this->setb('emailsettings', $estis);
        }
        $data = $this->getb('emailsettings');
        if(empty($data)){
            $data = [
                'host' => '',
                'port' => 25,
                'user' => '',
                'password' => '',
                'secure' => 'tls',
                'auth' => 1
            ];
            $ceshi = 0;
        }
        else{
            $data = unserialize($data);
            $ceshi = 1;
        }
        $this->assign('data', $data);
        $this->assign('ceshi', $ceshi);
        $this->assign('backstageMenu', 'xitong');
        $this->assign('option', 'smtpsettings');
        return $this->view();
    }
    public function sendtestmail()
    {
        $this->checkPermissions(1);
        $estis = unserialize($this->getb('emailsettings'));
        if($this->sendmail($estis['user'], '', Lang::get('Test mail'), Lang::get('This is a test email'))){
            echo 'ok';
        }
        else{
            echo Lang::get('Test mail failed to send');
        }
        exit();
    }
}