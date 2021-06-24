<?php

namespace App\Controllers;

class HomeController
{
	function index()
	{
		return view('index');
	}

	function install()
	{
		return view('install');
	}

	public function load()
    {
        if (
            !file_exists(
                getPath('views/pages/' . request('view') . '.blade.php')
            )
        ) {
            abort(__("File not found!"), 201);
        }
        return view('pages.' . request('view'));
    }

}
