<?php

/*
 * Weak reference that can live inside objects copied to other threads.
 *
 * PHP's WeakReference cannot be serialized, so a thread holding any object which
 * (indirectly) stores one fails when pthreads copies the object. The reference
 * itself is kept in a static table, which never takes part in serialization; the
 * referencing object only carries an integer handle.
 */

class WeakRef{

	/** @var \WeakReference[] */
	private static $refs = [];
	private static $counter = 0;

	private $id;

	public function __construct($object){
		if(!\is_object($object)){
			throw new \InvalidArgumentException("WeakRef expects an object, " . \gettype($object) . " given");
		}
		if(\count(self::$refs) > 1024){
			self::purge();
		}
		$this->id = ++self::$counter;
		self::$refs[$this->id] = \WeakReference::create($object);
	}

	/**
	 * @return object|null
	 */
	public function get(){
		if(!isset(self::$refs[$this->id])){
			return \null;
		}
		$object = self::$refs[$this->id]->get();
		if($object === \null){
			unset(self::$refs[$this->id]);
		}

		return $object;
	}

	/**
	 * @return bool
	 */
	public function valid(){
		return $this->get() !== \null;
	}

	private static function purge(){
		foreach(self::$refs as $id => $ref){
			if($ref->get() === \null){
				unset(self::$refs[$id]);
			}
		}
	}
}
