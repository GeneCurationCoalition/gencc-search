<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Setting;



class AllowPosts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gencc:allow-posts {value=yes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Control setting (yes/no) that allows posts from gencc-sub';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        switch ($this->argument('value'))
        {
            case "yes":
            case "no":
                Setting::set('allow_posts', $this->argument('value'));
                $token_posts = Setting::get('token_posts');
                if ($token_posts == null) {
                    Setting::set('token_posts', 'eKtvHtwlWB1y7Q5MxcLwWyjYF55Ltvs0ITWlAi8UNAcXZkzZiYUi0v7HvMOr');
                }
                Setting::save();
                break;
            default:
                break;
        }

    }

}
