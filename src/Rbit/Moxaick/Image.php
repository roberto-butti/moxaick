<?php
/**
 *
 */

namespace Rbit\Moxaick;

use Rbit\Moxaick\Logger;

class Image
{
    private $basedir = "./";
    private $filename;

    private $image;

    private $width;
    private $height;

    private $cell_width;
    private $cell_height;

    public function __construct($filename = null)
    {
        Logger::log(__METHOD__." - ".$filename);
        $this->filename = $filename;
    }

    public function setFilename($filename) {
        $this->filename = $filename;
    }

    public function getAbsolutePathFile() {
        return $this->basedir.$this->filename;
    }

    public function load_image()
    {
        $fn = $this->getAbsolutePathFile();

        Logger::log(__METHOD__." - ".$fn);

        $this->image = new \imagick($fn);
        if ( file_exists( $fn ) ) {
            Logger::log(__METHOD__." - EXISTS");
        } else {
            Logger::log(__METHOD__ . " - NOT EXISTS");
            return false;
        }
        $this->geo=$this->image->getImageGeometry();
        $this->width = $this->geo["width"];
        $this->height = $this->geo["height"];
        Logger::log(__METHOD__ . " - width : ".$this->width);
        Logger::log(__METHOD__ . " - height: ".$this->height);

    }

    /**
     * @param $rows
     * @param $columns
     * @throws \Exception
     */
    public function split_cells( $rows ,  $columns, $generate_thumb = false)  {


        $cell_dir = "test/cells/";
        if(!is_dir($cell_dir)) throw new \Exception('"'.$cell_dir.'" does not exist');

        if($this->width % $columns)
            throw new \Exception($columns.' not a multiple of '.$this->width);
        if($this->height % $rows)
            throw new \Exception($rows.' not a multiple of '.$this->height);
        $this->cell_width  = $this->width  / $columns;
        $this->cell_height = $this->height / $rows;
        Logger::log(__METHOD__ . " - Cell width : ".$this->cell_width);
        Logger::log(__METHOD__ . " - Cell height: ".$this->cell_height);

        Logger::log(__METHOD__ . " - Columns    : ".$columns);
        Logger::log(__METHOD__ . " - Rows       : ".$rows);

        $matrix = array();
        $mosaic = new \Imagick();

        $mosaic->newImage($this->width, $this->height, new \ImagickPixel("white"));
        for ($x =0 ; $x<$columns; $x++) {
            for ($y =0 ; $y<$rows; $y++) {
                $current_cell = clone $this->image;
                $curr_x = $x * $this->cell_width;
                $curr_y = $y * $this->cell_height;

                $current_cell->cropImage($this->cell_width, $this->cell_height, $curr_x, $curr_y);
                $pixels=$current_cell->getImageHistogram();
                $colors_max = array();
                $count_max = 0;
                foreach($pixels as $p){
                    $colors = $p->getColor();
                    //foreach($colors as $c){
                        //print( "$c\t" );
                    //}

                    //print( "\t:\t" . $p->getColorCount() . "\n" );
                    if ($p->getColorCount() > $count_max) {
                        $count_max = $p->getColorCount();
                        $colors_max = $colors;
                    }
                }
                $matrix[$x][$y] = $colors_max;

                $file_name = $cell_dir.$curr_x.'_'.$curr_y.'.jpg';
                if ($generate_thumb) {
                    $current_cell->writeImage($file_name);
                }
                $current_cell->clear();
                unset($current_cell);
                Logger::log($file_name. " - ". print_r($colors_max, true)." - memory:".memory_get_usage());

                $tile_color = new \Imagick();
                $rgb_color= "rgb(".$colors_max['r'].",".$colors_max['g'].",".$colors_max['b'].")";
                $tile_color->newImage($this->cell_width, $this->cell_height, new \ImagickPixel($rgb_color));
                $tile_color->setImageFormat('png');
                if ($generate_thumb) {
                    $tile_color->writeImage($cell_dir."tiles_".$curr_x.'_'.$curr_y.'.jpg');
                }


                $draw = new \ImagickDraw();
                $strokeColor = new \ImagickPixel($rgb_color);
                $fillColor = new \ImagickPixel($rgb_color);

                $draw->setStrokeColor($strokeColor);
                $draw->setFillColor($fillColor);
                $draw->setStrokeOpacity(1);
                $draw->setStrokeWidth(0);

                $draw->rectangle($curr_x, $curr_y, $curr_x+$this->cell_width, $curr_y+$this->cell_height);
                $mosaic->drawImage($draw);

                //imagedestroy($current_cell);
            }
            $mosaic->setImageFormat('png');
            $mosaic->writeImage($cell_dir."mosaic.png");

        }


    }

    public function unload_image() {
        //imagedestroy($this->image);
    }





}