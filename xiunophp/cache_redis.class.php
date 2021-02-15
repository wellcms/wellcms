<?php

class cache_redis
{

    public $conf = array();
    public $link = NULL;
    public $cachepre = '';
    public $errno = 0;
    public $errstr = '';

    public function __construct($conf = array())
    {
        if (!extension_loaded('Redis')) {
            return $this->error(-1, ' Redis 扩展没有加载');
        }
        $this->conf = $conf;
        $this->cachepre = isset($conf['cachepre']) ? $conf['cachepre'] : 'pre_';
    }

    public function connect()
    {
        if ($this->link) return $this->link;
        $redis = new Redis;
        $r = $redis->connect($this->conf['host'], $this->conf['port']);
        if (!$r) {
            return $this->error(-1, '连接 Redis 服务器失败。');
        }
        //$redis->auth('foobared'); // 密码验证
        //$redis->select('wellcms'); // 选择数据库wellcms
        $this->link = $redis;
        return $this->link;
    }

    /* wellcms@qq.com
     * @param $k 缓存键
     * @param $v 缓存值
     * @param int $life 缓存生命周期
     * @param int $timeout 0 默认时秒 1 毫秒
     * @param null $lock 1 键不存在时设置键(锁) 2 键已存在时才设置(锁)
     * @return bool 设置成功返回1 返回0表示有锁
     */
    public function set($k, $v, $life = 0, $timeout = 0, $lock = NULL)
    {
        if (!$this->link && !$this->connect()) return FALSE;
        $v = xn_json_encode($v);
        //EX时秒 PX毫秒
        $type = $timeout ? 'PX' : 'EX';
        if (1 == $lock) {
            $lock = 'NX'; // NX 仅在键不存在时设置键(上锁)
        } elseif (2 == $lock) {
            $lock = 'XX'; // XX 只有在键已存在时才设置(上锁)
        }
        if ($life) {
            $r = $this->link->set($k, $v, array($type => $life, $lock));
        } else {
            //永久不过期
            $r = $this->link->set($k, $v);
        }
        return $r;
    }

    public function get($k)
    {
        if (!$this->link && !$this->connect()) return FALSE;
        $r = $this->link->get($k);
        return FALSE === $r ? NULL : xn_json_decode($r);
    }

    public function delete($k)
    {
        if (!$this->link && !$this->connect()) return FALSE;
        return $this->link->del($k) ? TRUE : FALSE;
    }

    public function truncate()
    {
        if (!$this->link && !$this->connect()) return FALSE;
        return $this->link->flushdb(); // flushall
    }

    public function error($errno = 0, $errstr = '')
    {
        $this->errno = $errno;
        $this->errstr = $errstr;
        DEBUG AND trigger_error('Cache Error:' . $this->errstr);
    }

    public function __destruct()
    {
        if ($this->link) {
            $this->link->close();
        }
    }
}

?>