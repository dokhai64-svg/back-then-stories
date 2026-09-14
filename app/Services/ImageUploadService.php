<?php
namespace App\Services;
use Illuminate\Http\UploadedFile; use Illuminate\Support\Facades\Storage; use Illuminate\Support\Str;
class ImageUploadService {
 public function store(UploadedFile $file,string $folder='media'):array{
   $disk='public'; $name=Str::uuid()->toString(); $bytes=file_get_contents($file->getRealPath());
   if(function_exists('imagecreatefromstring') && function_exists('imagewebp')){
      $im=@imagecreatefromstring($bytes); if($im!==false){ imagepalettetotruecolor($im); ob_start(); imagewebp($im,null,84); $webp=ob_get_clean(); imagedestroy($im); $path=$folder.'/'.$name.'.webp'; Storage::disk($disk)->put($path,$webp); return ['disk'=>$disk,'path'=>$path,'filename'=>$file->getClientOriginalName(),'mime_type'=>'image/webp','size'=>strlen($webp)]; }
   }
   $ext=strtolower($file->getClientOriginalExtension() ?: 'jpg'); $path=$file->storeAs($folder,$name.'.'.$ext,$disk); return ['disk'=>$disk,'path'=>$path,'filename'=>$file->getClientOriginalName(),'mime_type'=>$file->getMimeType(),'size'=>$file->getSize()];
 }
}
