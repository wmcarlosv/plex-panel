<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use TCG\Voyager\Models\Setting;

class AdminSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $setting = $this->findSetting('admin.dynamic_server');

        if (!$setting->exists) {
            $setting->fill([
                'display_name' => 'Servidor Dinamico',
                'value'        => false,
                'details'      => '',
                'type'         => 'checkbox',
                'order'        => 1,
                'group'        => 'Admin'
            ])->save();
        }

        $setting = $this->findSetting('admin.iphone_for_all');

        if (!$setting->exists) {
            $setting->fill([
                'display_name' => 'Convertir a Iphone Para Todos',
                'value'        => false,
                'details'      => '',
                'type'         => 'checkbox',
                'order'        => 1,
                'group'        => 'Admin',
            ])->save();
        }

        $setting = $this->findSetting('admin.show_only_server_local_name');

        if (!$setting->exists) {
            $setting->fill([
                'display_name' => 'Mostrar solo el nombre local en los servidores',
                'value'        => false,
                'details'      => '',
                'type'         => 'checkbox',
                'order'        => 1,
                'group'        => 'Admin',
            ])->save();
        }

        $setting = $this->findSetting('admin.iphone_only_server');

        if (!$setting->exists) {
            $setting->fill([
                'display_name' => 'Mostrar Servidores Solo Para Convertir a Iphone',
                'value'        => false,
                'details'      => '',
                'type'         => 'checkbox',
                'order'        => 1,
                'group'        => 'Admin',
            ])->save();
        }

        $setting = $this->findSetting('admin.server_for_alls');

        if (!$setting->exists) {
            $setting->fill([
                'display_name' => 'Mostrar Servidores Para Cuentas y Demos',
                'value'        => false,
                'details'      => '',
                'type'         => 'checkbox',
                'order'        => 1,
                'group'        => 'Admin',
            ])->save();
        }

        $setting = $this->findSetting('admin.screen_message');

        if (!$setting->exists) {
            $setting->fill([
                'display_name' => 'Mensaje para mostrar cuando sobre pasen las pantallas Asignadas',
                'value'        => "",
                'details'      => '',
                'type'         => 'text',
                'order'        => 1,
                'group'        => 'Admin',
            ])->save();
        }

        $setting = $this->findSetting('admin.extra_options_limited');

        if (!$setting->exists) {
            $setting->fill([
                'display_name' => 'Opciones Extras Limitadas',
                'value'        => false,
                'details'      => '',
                'type'         => 'checkbox',
                'order'        => 1,
                'group'        => 'Admin',
            ])->save();
        }

        $setting = $this->findSetting('admin.show_ip_address_all');

        if (!$setting->exists) {
            $setting->fill([
                'display_name' => 'Mostrar Ip Proxy A Todos Roles',
                'value'        => false,
                'details'      => '',
                'type'         => 'checkbox',
                'order'        => 1,
                'group'        => 'Admin',
            ])->save();
        }

        $setting = $this->findSetting('admin.add_account_not_password_for_all');

        if (!$setting->exists) {
            $setting->fill([
                'display_name' => 'Agregar Cuentas Sin Clave Para Todos',
                'value'        => false,
                'details'      => '',
                'type'         => 'checkbox',
                'order'        => 1,
                'group'        => 'Admin',
            ])->save();
        }


    $setting = $this->findSetting('admin.account_expiration_days');

        if (!$setting->exists) {
            $setting->fill([
                'display_name' => 'Dias de Aviso de Expiracion de Cuentas',
                'value'        => 0,
                'details'      => '',
                'type'         => 'text',
                'order'        => 1,
                'group'        => 'Admin',
            ])->save();
        }

    }

    protected function findSetting($key)
    {
        return Setting::firstOrNew(['key' => $key]);
    }
}
