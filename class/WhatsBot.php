<?php
	require_once 'Lib/_Loader.php';

	require_once 'WhatsAPI/whatsprot.class.php';

	require_once 'WhatsApp.php';

	require_once 'Listener.php';
	require_once 'Parser.php';

	require_once 'ModuleManager.php';

	class WhatsBot
	{
		private $WhatsProt = null;
		private $WhatsApp = null;

		private $Listener = null;
		private $Parser = null;

		private $ModuleManager = null;

		private $Debug = false;
		private $StartTime = null;

		public function __construct($Debug = false)
		{
			Std::Out();
			Std::Out('[INFO] [WHATSBOT] Loading. Debug = ' . var_export($Debug, true));

			$this->Debug = $Debug;

			Config::Load();
			LoadLibs();

			$Config = Config::Get('WhatsBot');

			if(!empty($Config['WhatsApp']['Username']) && !empty($Config['WhatsApp']['Nickname']))
			{
				# WhatsApp

				Std::Out();
				Std::Out("[INFO] [WHATSBOT] I'm {$Config['WhatsApp']['Nickname']} ({$Config['WhatsApp']['Username']})");

				$this->WhatsProt = new WhatsProt($Config['WhatsApp']['Username'], $Config['WhatsApp']['Nickname'], $Debug);

				$this->WhatsApp = new WhatsApp($this->WhatsProt);

				# WhatsBot

				$this->ModuleManager = new ModuleManager($this, $this->WhatsApp);

				$this->Parser = new WhatsBotParser($this->WhatsApp, $this->ModuleManager);

				$this->Listener = new WhatsBotListener($this->WhatsApp, $this->Parser);

				# Load

				$this->ModuleManager->LoadModules();

				# Binding

				$this->WhatsApp->EventManager()->BindListener($this->Listener);
			}
			else
				throw new Exception('You have to setup the config file config/WhatsBot.json');
		}

		public function Start()
		{
			$Config = Config::Get('WhatsBot');

			if(!empty($Config['WhatsApp']['Password']))
			{
				Std::Out();
				Std::Out('[INFO] [WHATSBOT] Connecting');

				if($this->WhatsApp->Connect())
					Std::Out('[INFO] [WHATSBOT] Connected!');
				else
					Std::Out('[WARNING] [WHATSBOT] Connection error');

				Std::Out();
				Std::Out('[INFO] [WHATSBOT] Logging in');
				$this->WhatsApp->LoginWithPassword($Config['WhatsApp']['Password']);
				Std::Out('[INFO] [WHATSBOT] Ready!');
			}
			else
				throw new Exception('You have to setup the whatsapp password in config/WhatsBot.json');
		}

		public function Listen()
		{
			$Time = $this->StartTime = time();

			Std::Out();
			Std::Out("[INFO] [WHATSBOT] Start time is {$this->StartTime}");

			Std::Out();
			Std::Out('[INFO] [WHATSBOT] Listening...');

			while(true)
			{
				if(!$this->WhatsApp->IsConnected())
					$this->Start();

				$this->WhatsApp->PollMessage();

				if(time() >= $Time + 60)
				{
					$this->WhatsApp->SendPing();

					$Time = time();
				}
			}
		}


		public function GetStartTime()
		{ return $this->StartTime; }
	}

	/* 
	 * To do: 
	 * 
	 * Flood protection
	 * https://github.com/mgp25/WhatsAPI-Official/issues/164#issuecomment-64821350
	 *  - Sync before send message. Sync if not in array. If disconnect reset array
	 * Fix #28 reconnect issue
	 * CLI only
	 * Implement WhatsApp workflow (https://github.com/mgp25/WhatsAPI-Official/wiki/WhatsAPI-Documentation#whatsapp-workflow)
	 * 
	 *****
	 * 
	 * Ideas: 
	 * 
	 * Detect lang from country code
	 * 
	 *****
	 * Questions: 
	 * 
	 * Send presence?
	 */