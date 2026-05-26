<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Governorate;
use App\Models\City;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
       $data=[
        'دمشق'=>
        [
            'المزة',
            'المالكي',
            'أبو رمانة',
            'الميدان',
            'ركن الدين',
              'برزة ' ,
              'كفرسوسة',
              'الشيخ محي الدين'
        ],
         'ريف دمشق'=>
         [
            'دوما',
            'حرستا',
            'جرمانا',
            'داريا',
            'قدسيا',
            'التل',
         ],
         'حلب'=>
         [
            'حلب',
            'منبج',
            'الباب',
            'اعزاز',
            'عفرين',
            'السفيرة',
         ],
         'حمص'=>
         [
            'حمص',
            'تدمر',
            'الرستن',
            'تلبيسة',
            'القصير',
         ],
          'حماة'=>
          [
            'حماة',
            'السلمية',
            'مصياف',
            'محردة',
            'صوران',
          ],
          'اللاذقية'=>
          [
            'اللاذقية',
             'جبلة',
             'القرداحة',
             'الحفة',
          ],
          'طرطوس'=>
          [
            'طرطوس',
            'بانياس',
            'صافيتا',
            'الشيخ بدر',
          ],
          'ادلب'=>
          [
            'ادلب',
            'معرة النعمان',
            'أريحا',
            'سراقب',
            'جسر الشغور',
          ],
          'درعا'=>
          [
            'درعا',
            'نوى',
            'ازرع',
            'الصنمين',
          ],
          'السويداء'=>
          [
            'السويداء',
            'شهبا',
            'صلخدا',
          ],
          'القنيطرة'=>
          [
            'القنيطرة',
            'البعث',
            'خان ارنبة',
          ],
          'دير الزور'=>
          [
            'دير الزور',
            'الميادين',
            'البوكمال',
          ],
          'الرقة'=>
          [
            'الرقة',
            'الطبقة',
            'تل أبيض' ,
          ],
          'الحسكة'=>
          [
            'الحسكة',
            'القامشلي',
            'رأس العين',
            'المالكية',
          ],
       ];
       foreach($data as $governorateName=>$cities)
        {
          $governorate=Governorate::create([
            'name'=>$governorateName,
          ]);
          foreach($cities as $cityName)
            {
              City::create([
                'name'=>$cityName,
                'governorate_id'=>$governorate->id,
              ]);
            }
        }
    }

}
