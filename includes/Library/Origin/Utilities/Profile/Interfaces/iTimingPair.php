<?php
namespace Origin\Utilities\Profile\Interfaces;

interface iTimingPair extends \JsonSerializable {
	public function Start();
	public function Stop();
	public function Difference();
}