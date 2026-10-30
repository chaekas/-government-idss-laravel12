<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Farmer extends Model {
 protected $fillable=['farmer_code','full_name','phone','email','national_id','region','district','ward','village','address'];
}
