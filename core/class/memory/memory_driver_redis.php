<?php

class memory_driver_redis
{
	var $enable;
	var $obj;

	function init($config) {
		if(!empty($config['server'])) {
			try {
				$this->obj = new Redis();
				if($config['pconnect']) {
					$connect = @$this->obj->pconnect($config['server'], $config['port']);
				} else {
					$connect = @$this->obj->connect($config['server'], $config['port']);
				}
               
			} catch (RedisException $e) {
			    echo $e;
			}
			$this->enable = $this->checkEnable($connect);
			if($this->enable) {
				if($config['requirepass']) {
					$this->obj->auth($config['requirepass']);
				}
				@$this->obj->setOption(Redis::OPT_SERIALIZER, $config['serializer']);
			}
		}
	}
	public function checkEnable($connect){
		if($connect){
			$this->set('_check_','_check_',10);
			if($this->get('_check_')=='_check_'){
				return true;
			}
			$this->rm('_check_');
		}
		return false;
	}
    public function &instance() {
		static $object;
		if(empty($object)) {
			$object = new memory_driver_redis();
			$object->init(getglobal('config/memory/redis'));
		}
		return $object;
	}

    public function get($key) {
		if(is_array($key)) {
			return $this->getMulti($key);
		}
		return $this->obj->get($key);
	}

    public function getMulti($keys) {
        if(method_exists($this->obj, 'getMultiple')){
           $result = $this->obj->getMultiple($keys);
        }else if(method_exists($this->obj, 'mget')){
            $result = $this->obj->mget($keys);
        }
		$newresult = array();
		$index = 0;
		foreach($keys as $key) {
			if($result[$index] !== false) {
				$newresult[$key] = $result[$index];
			}
			$index++;
		}
		unset($result);
		return $newresult;
	}

    public function select($db=0) {
		return $this->obj->select($db);
	}

    public function set($key, $value, $ttl = 0) {
		if($ttl) {
			return $this->obj->setex($key, $ttl, $value);
		} else {
			return $this->obj->set($key, $value);
		}
	}

    public function rm($key) {
		return $this->obj->delete($key);
	}

    public function setMulti($arr, $ttl=0) {
		if(!is_array($arr)) {
			return FALSE;
		}
		foreach($arr as $key => $v) {
			$this->set($key, $v, $ttl);
		}
		return TRUE;
	}

    public function inc($key, $step = 1) {
		return $this->obj->incr($key, $step);
	}

    public function dec($key, $step = 1) {
		return $this->obj->decr($key, $step);
	}

    public function getSet($key, $value) {
		return $this->obj->getSet($key, $value);
	}

    public function sADD($key, $value) {
		return $this->obj->sADD($key, $value);
	}

    public function sRemove($key, $value) {
		return $this->obj->sRemove($key, $value);
	}

    public function sMembers($key) {
		return $this->obj->sMembers($key);
	}

    public function sIsMember($key, $member) {
		return $this->obj->sismember($key, $member);
	}

    public function keys($key) {
		return $this->obj->keys($key);
	}

    public function expire($key, $second){
		return $this->obj->expire($key, $second);
	}

    public function sCard($key) {
		return $this->obj->sCard($key);
	}

    public function hSet($key, $field, $value) {
		return $this->obj->hSet($key, $field, $value);
	}

    public function hDel($key, $field) {
		return $this->obj->hDel($key, $field);
	}

    public function hLen($key) {
		return $this->obj->hLen($key);
	}

    public function hVals($key) {
		return $this->obj->hVals($key);
	}

    public function hIncrBy($key, $field, $incr){
		return $this->obj->hIncrBy($key, $field, $incr);
	}

    public function hGetAll($key) {
		return $this->obj->hGetAll($key);
	}

    public function sort($key, $opt) {
		return $this->obj->sort($key, $opt);
	}

    public function exists($key) {
		return $this->obj->exists($key);
	}

    public function clear() {
		return $this->obj->flushAll();
	}
}
?>