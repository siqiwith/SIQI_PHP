<?php
class SQ_Captcha{
	/**
	 * Alphabet
	 * 
	 * @access private
	 * @var string
	 */
	private $alpha = "abcdefghijkmnopqrstuvwxyz";
	
	/**
	 * Numbers
	 * 
	 * @access private
	 * @var string
	 */
	private $number = "0123456789";
	
	/**
	 * Captcha image height
	 * 
	 * @var int
	 */
	public $image_height = 30;
	
	/**
	 * Captcha image width
	 * 
	 * @var int
	 */
	public $image_width = 120;
	
	/**
	 * Captcha character number
	 * 
	 * @var int
	 */
	public $character_num = 6;
	
	/**
	 * Font size
	 * 
	 * @var int
	 */
	public $font_size = 5;
	
	public $x_offset = 5;
	public $y_offset = 5;
	public $spacing = 20;
	
	/**
	 * Captcha string and colors
	 * 
	 * @var array
	 */
	public $captcha_info = null;
	
	/**
	 * Captcha string
	 *
	 * @var string
	 */
	public $captcha_string = "";
	
	function generate_captcha(){
		$this->captcha_string = "";
		$this->generate_captcha_info();
		$this->generate_auth_image();
	}
	
	function generate_captcha_info(){
		$this->captcha_info = array();
		for($i = 0; $i < $this->character_num; $i++){
			$alpha_or_number = mt_rand(0, 1);
			$str = $alpha_or_number ? $this->alpha : $this->number;
			$character = substr($str, mt_rand(0, strlen($str) - 1), 1);
			$rgb_color = array(mt_rand(100, 255), mt_rand(100, 255), mt_rand(100, 255));
			$this->captcha_info[] = array("character" => $character, "rgb_color" => $rgb_color);
			$this->captcha_string = $this->captcha_string.$character;
		}
	}
	
	function generate_auth_image(){
		if(!is_array($this->captcha_info)){
			return false;
		}
		$image = ImageCreate($this->image_width, $this->image_height);
		$bg_color = ImageColorAllocate($image, 255, 255, 255);
		ImageFill($image, 0, 0, $bg_color);
		foreach($this->captcha_info as $index => $character_info){
			$color = ImageColorAllocate($image, $character_info["rgb_color"][0], $character_info["rgb_color"][1], $character_info["rgb_color"][2]);
			ImageChar($image, $this->font_size, $this->x_offset + $index * $this->spacing, $this->y_offset, $character_info["character"], $color);
		}
		$this->confuse_image($image);
		$this->image = $image;
	}
	
	function confuse_image(&$image){
		//add lines
		for($i=0; $i<5; $i++)
		{
			$line_color = ImageColorAllocate($image, mt_rand(0,255), mt_rand(0,255), mt_rand(0,255));
			ImageArc($image, mt_rand(-5, $this->image_width), mt_rand(-5, $this->image_height), mt_rand(20, 300), mt_rand(20, 200), 55, 44, $line_color);
		}
		//add spots
		for($i=0; $i<$how*40; $i++)
		{
			$spot_color = ImageColorAllocate($image, mt_rand(0,255), mt_rand(0,255), mt_rand(0,255));
			ImageSetPixel($image, mt_rand(0,$this->image_width), mt_rand(0,$this->image_height), $spot_color);
		}
	}
	
	function output_captcha_image(){
		imagepng($this->image);
		imagedestroy($this->image);
	}
}
?>