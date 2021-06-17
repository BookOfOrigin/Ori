<?php
namespace Origin\Utilities\Profile\Interfaces;

interface iTimingHandler extends \JsonSerializable {
	public function Start();
	public function Stop();
	public function Name($name = null);
	public function File($file = null);
	public function Line($line = null);
	public function Stack();
	public function Difference();
	public function Format();
}