<?php

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        $this->call(ServersTableSeeder::class);
        $this->call(ServerConfigurationsTableSeeder::class);

        Model::reguard();
    }
}

class ServersTableSeeder extends Seeder
{
    public function run()
    {
        \App\Server::create(['id' => 1, 'scheme' => 'sftp', 'host' => '192.168.56.56']);
        \App\Server::create(['id' => 2, 'scheme' => 'ftp', 'host' => '192.168.56.1']);
        \App\Server::create(['id' => 3, 'scheme' => 'sftp', 'host' => 'unknown.local']);
        \App\Server::create(['id' => 4, 'scheme' => 'sftp', 'host' => 'noconfig.local']);
    }
}

class ServerConfigurationsTableSeeder extends Seeder
{
    public function run()
    {
        \App\ServerConfiguration::create(['auth' => 'user1:pass1', 'path' => '/wefwefwe/wef', 'server_id' => 1]);
        \App\ServerConfiguration::create(['auth' => 'user2:pass2', 'path' => '/wefwefwe/wef', 'server_id' => 1]);
        \App\ServerConfiguration::create(['auth' => 'root:vagrant', 'path' => '/tmp', 'server_id' => 1]);
        \App\ServerConfiguration::create(['auth' => 'root:vagrant', 'server_id' => 2]);
        \App\ServerConfiguration::create(['server_id' => 2]);
        \App\ServerConfiguration::create(['auth' => 'user1:pass1', 'path' => '/wefwefwe/wef', 'server_id' => 3]);
    }
}