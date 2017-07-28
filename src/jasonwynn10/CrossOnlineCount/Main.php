<?php
namespace jasonwynn10\CrossOnlineCount;

use jasonwynn10\CrossOnlineCount\libs\MinecraftQuery;

use pocketmine\event\Listener;
use pocketmine\nbt\tag\StringTag;
use pocketmine\plugin\PluginBase;
use slapper\events\SlapperCreationEvent;
use slapper\events\SlapperDeletionEvent;

class Main extends PluginBase implements Listener {
	/** @var MinecraftQuery $Query */
	private $query;
	private $task;
	public function onEnable() {
		$arr = [];
		foreach($this->getServer()->getLevels() as $level) {
			if(!$level->isClosed()) {
				foreach($level->getEntities() as $entity) {
					if(isset($entity->namedtag->server)) {
						$ip = $entity->namedtag->server->getValue();
						$arr[$entity->getId()] = $ip;
					}
				}
			}
		}
		$this->getServer()->getScheduler()->scheduleRepeatingTask($this->task = new UpdateTask($this, $arr, $this->query = new MinecraftQuery()), 5);
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}
	public function onDisable(){
		foreach($this->task->arr as $eid => $ip) {
			foreach($this->getServer()->getLevels() as $level) {
				if(!$level->isClosed()) {
					$entity = $level->getEntity($eid);
					//TODO save positions of other server data on entity nametag
				}
			}
		}
	}
	public function onSlapperCreate(SlapperCreationEvent $ev) {
		$entity = $ev->getEntity();
		if(preg_match("/(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}):(\d{1,5})/", $entity->getNameTag(), $matches) == 1){
			$entity->namedtag->server = new StringTag("server", $matches[0] ?? "");
			$this->task->arr[$entity->getId()] = $matches[0] ?? "";
		}
	}
	public function onSlapperDelete(SlapperDeletionEvent $ev) {
		$entity = $ev->getEntity();
		if(isset($this->task->arr[$entity->getId()])) {
			unset($this->task->arr[$entity->getId()]);
		}
		if(isset($entity->namedtag->server)) {
			unset($entity->namedtag->server);
		}
	}
}