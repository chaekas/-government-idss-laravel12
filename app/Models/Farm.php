<?php
namespace App\\Models;
use Illuminate\\Database\\Eloquent\\Model;
class Farm extends Model {
 protected $fillable=['farm_code','farmer_id','name','region','district','ward','village','latitude','longitude','poultry_type','bird_count','notes'];
}
