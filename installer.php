<?php

class Csv2Json
{
    public $uniqueParts;
    public $csvBookName;
    public $BookName;
	public $pagebuffer;
	public $history;
		
	public function __construct()
	{
		$this->pagebuffer = '';
		
		$this->csvBookName = $this->fixpath(__DIR__.'/../d22-private/Csv Books/Book 000/Page 000.csv');
		
		if (!file_exists($this->csvBookName))
		{
			return;
		}

		$this->BookTitle = $this->BookName($this->csvBookName);
		
		///echo '<div>Title: "'.$this->BookTitle.'"';
	}
	public function clean_url(string $ledger = ''): string
	{
		$pattern = '/(?<method>href|src|source|poster)="(?<url>[^"]* [^"]*)"/mi';
		
		while(preg_match($pattern, $ledger, $matches))
		{
			$replacements['url']=$matches['url'];
			$replacements['url']=$this->fixpath($replacements['url']);
			
			foreach
			(
				[
				' ' => '%20',
				'\'' => '%27'
				]
				as $search => $replace
			)
			{
				$replacements['url']=str_replace($search,$replace,$replacements['url']);
			}
			
			$ledger = str_replace($matches['url'],$replacements['url'],$ledger);
		}
		return $ledger;
	}
	
	public function importCsvToJson(string $csvFilename = "",string $jsonFilename = "")
    {
		if (!file_exists($csvFilename))
		{
			return;
		}

		$csvFilename = $this->fixpath($csvFilename);
		
		///echo '<div>Title: "'.$this->BookTitle.'"';
		
		preg_match('/\/Books\/Book (?<book>[0-9]{3})\/Page (?<page>[0-9]{3})(?<ext>\.csv)/mxi',$csvFilename,$fileParts);

		$fileParts = array_merge($fileParts, explode ('/', $csvFilename));
		
		$page = [];
		
        // Open the CSV file for reading
        // Check if the file was opened successfully
		if(file_exists($csvFilename))
		{
			$field = explode( "\r\n",file_get_contents($csvFilename));
			$lineCount=count($field);
			
			$pattern_list = file_get_contents(__DIR__.'/../d22-private/patterns/pattern list.csv');
			
			//echo '<pre>list: "'.$pattern_list.'"</pre>';
			
			$pattern_list = explode(',', $pattern_list);
            
			foreach($pattern_list as $attribute)
			{
				$patterns[$attribute] = file_get_contents(__DIR__.'/../d22-private/patterns/' . $attribute . '.pattern');
				
				//echo '<pre>'.$attribute.': "'.$patterns[$attribute].'"</pre>';
				
				$patterns[$attribute] = explode("\r\n",$patterns[$attribute]);
				
				$patterns[$attribute] = $patterns[$attribute][1];
				
				$page[$attribute]['count'] = 0;

				//echo '<pre>'.$this->fixpath(json_encode(array($attribute,$patterns[$attribute]),JSON_PRETTY_PRINT)).'</pre>';
			}
			
			foreach($field as $index => $row)
			{
				foreach ($patterns as $attribute => $pattern)
				{				
					if(isset($pattern)?preg_match($pattern,$row,$matches) > 0:false)
					{
						if(isset($page[$attribute]['count']) == false)
						{
							$page[$attribute]['count'] = 0;
						}
						$page[$attribute]['count']++;
						
						foreach($matches as $key => $value)
						{
							if(is_int($key))
							{
								unset($matches[$key]);
							} else {	
								switch($key)
								{
								case 'key':
									$temp = $matches[$key];
									switch($temp)
									{
									case ' ':
										$matches[$key] = 'SP';
										break 1;
									default:
										break 1;
									}
									break 1;
									
								default:
									break 1;
								}
								switch($key)
								{	
								case 'entity_count':
								case 'option_count':
								case 'dest':
								case 'preq':
									
									$temp = $this->strip($matches[$key]);
									
									$matches[$key] = $temp['value'];
									
									if ( $temp['sign'] < 0 ) {
										$matches[$key] = -$matches[$key];
									}
									
									switch($key)
									{
									case 'dest':
									case 'preq':
										
										$matches[$key] = $temp['value'];
										
										$matches['condition'] = $temp['condition'];
										
										break 1;
										
									default:
										break 1;
									}
									
								default:
									break 1;
								}
							}
						}
						
						$page[$attribute][$page[$attribute]['count']]=$matches;
						
						//echo '<pre>'.json_encode(array($attribute,$matches),JSON_PRETTY_PRINT).'</pre>';
						
						break 1;
					}
				}
								
				//$page = [];
            }

        }
				
		// Convert the array to a pretty JSON string
		$jsonString = json_encode($page, JSON_PRETTY_PRINT);

		// Write the pretty JSON string to the file
		//file_put_contents($jsonFilename, $jsonString);
		return $jsonString;
    }

    public function preg_attr(string $field, string $attribute = ''): array
    {
        if ($this->attr_pattern($attribute) !== '')
		{
			return preg_match($this->attr_pattern($attribute), $field, $matches);
		} else {
			return [];
		}
	}

    public function attr_pattern(string $attribute = ''): string
    {
        $attribute_file = '/../d22-private/patterns/' . $attribute . '.pattern';
        $pattern = '';

        if (is_file($attribute_file)) {
            $pattern = file_get_contents($attribute_file);
            $pattern = substr($pattern, strpos($pattern, '\r\n') + strlen('\r\n'));
        }

        return $pattern;
    }
	public function fixpath(string $subject = "" ): string
	{
		return str_replace(':/','://',str_replace('\\','/',$subject));
	}

	public function _DIR_(): string
	{
		return $this->fixpath(__DIR__);
	}
	
	public function mkpath(string $path = "")
	{
		$path = realpath($path);
		$path = explode('/',$path);
		$path2 = '';
		$index = 0;
		foreach( $path as $level)
		{
			$index++;
			$path2 .= $level.'/';
			if(!is_dir($path2))
			{
				mkdir($path2);
			}
		}
	}
	
	public function csvFullImport(string $book = '000')
	{
		if(is_dir(__DIR__.'/../d22-private/Csv Books/Book '.$book.'/'))
		{
			$files = glob(__DIR__.'/../d22-private/Csv Books/Book '.$book.'/Page *.csv');
		} else {
			$files = [];
		}
		
		if(!is_dir(__DIR__.'/../d22-private/Json Books/Book '.$book.'/'))
		{
			$this->mkpath(__DIR__.'/../d22-private/Json Books/Book '.$book.'/');
		}

		foreach($files as $file)
		{
			
			$csvFilename = $this->fixpath($file);
			
			$jsonFilename = str_replace('.csv','.json',str_replace('/Books/','/Json Books/',$csvFilename));
			
			$page = json_decode( $this->importCsvToJson($csvFilename), true );

			preg_match
			(
				'/Book (?<book>[0-9]{3})\\/Page (?<page>[0-9]{3})/m',
				$csvFilename,
				$uniqueParts
			);
						
			foreach( ['book','page'] as $key)
			{
				$page['id'][$key] = $uniqueParts[$key];
			}
			
			$page['id']['unique'] = $page['caption'][1]['caption'].' '.$page['id']['page'];
			
			$page['id']['address'] = $jsonFilename;
						
			$page['id']['title'] = $this->BookTitle;
			
			$jsonString = json_encode( $page, JSON_PRETTY_PRINT );
			
			///echo '<pre>'.$jsonFilename.'</pre>'; 
			///echo '<pre>'.$jsonString.'</pre>'; 

			file_put_contents($page['id']['address'], $jsonString);
			
			$this->pagebuffer .= $this->disp_page ($page);
			
			//$this->pagebuffer .= $this->build_scene ($this->disp_page ($page));
		}
	}
	public function strip(string $subject = "0"): array
	{
		$prefix = substr($subject,0,1);
		$subject = substr($subject,1);
		
		switch($prefix)
		{
		case "-":
			$sign = -1;
			break 1;
		case "+":
			$sign = 1;
			break 1;
		default:
			$sign = 1;			
			$subject=$prefix.$subject;
			break 1;
		}
		
		$subject=ltrim($subject,'0');
		
		if(strlen($subject) == 0 )
		{
			$subject = '0';
		}
		
		if($subject == '0')
		{
			$sign = 0;
		}

		switch ( $sign )
		{
		case 0:
			$condition = 'elective';
			break 1;
		
		case 1:
			$condition = 'mandatory';
			break 1;
		
		case -1:
			$condition = 'contrary';
			break 1;
		
		default:
			break 1;
		}
		
		$condition = ucwords ($condition);
		
		return array('value' => intval($subject), 'sign' => $sign, 'condition' => $condition);
		
	}
	
	public function MakeUnique(string $caption = '', string $page = ''): string
	{
		$pageNum = $page;

		return $caption.' '.$pageNum;
		
	}
	
	public function BookName($csvFilename): string
	{
		$attribute = 'caption';
		
		$pattern = explode("\r\n",file_get_contents(__DIR__.'/../d22-private/patterns/' . $attribute . '.pattern'))[1];
		
		///echo '<pre>'.$attribute.': "'.$pattern.'"</pre>';
		
		$field = explode( "\r\n",file_get_contents($csvFilename));
		
		$lineCount=count($field);

		foreach($field as $index => $row)
		{
			if(isset($pattern)?preg_match($pattern,$row,$matches) > 0:false)
			{
				if(isset($page[$attribute]['count']) == false)
				{
					$page[$attribute]['count'] = 0;
				}
				$page[$attribute]['count']++;
						
				foreach($matches as $key => $value)
				{
					switch ( $key )
					{
					case 'caption':
						$this->BookTitle = $value;
						break 3;
				
					default:
						break 1;
					}
				}
			}
		}
		return $this->BookTitle;
	}
	public function page_view($page = [])
	{
		$this->history[$page['id']['title']]++;
	}
	public function disp_header($page = []): string
	{
		$result = '';

		for($index = 1;$index <= $page['header']['count']; $index++)
		{
			$p = $page['header'][$index];

			$template = '<div class="image-container"><img style="height: 100vh; width: 100vw;" src="{{url}}" alt="{{illustration}}">  <div class="header-overlay">{{illustration}}</div></div>'; 

			$template = str_replace( '{{url}}', '/Sprites/scene/'.$p['illustration'].'.png', $template);

			$template = str_replace( '{{illustration}}',$p['illustration'], $template);
			
			$result .= $template;
		}
		
		return $result;
	}
	public function disp_encounter( $page = []): string
	{
		$result = '';

		for($index = 1;$index <= $page['entity']['count']; $index++)
		{
			$p = $page['entity'][$index];
			
			$template = '<div class="image-container {{filter}}"><img style="height: 3em; width: auto;" src="{{url}}" alt="{{name}}"><div class="npc-overlay" style="font-size: 0.7em;">{{name}}&nbsp;({{mood}}{{count}})</div></div>';

			if($p['type'] == 'npc')
			{
				if(isset($p['count'])?$p['count'] == '':true)
				{
					$p['count'] = 1;
				}
			
				$template = str_replace('{{url}}','/Sprites/npc/'.$p['name'].'.png',$template);

				$template = str_replace('{{name}}',$p['name'],$template);
				
				$template = str_replace('{{filter}}',($p['mood']!=='.sprite.mood.'?''.strtolower($p['mood']):''),$template);

				$template = str_replace('{{mood}}',($p['mood']!==''?$p['mood'].'&nbsp;':''),$template);

				$template = str_replace('{{count}}',$p['count'].'x',$template);
				
				$result .=$template;
			}
		}
		return $result;
	}
	public function disp_bric_a_brac($page = []): string
	{
		$result = '';

		for($index = 1;$index <= $page['entity']['count']; $index++)
		{
			$p = $page['entity'][$index];
			
			$template = '<div class="image-container">{{tray}}<div class="item-overlay">{{name}}&nbsp;({{mood}}{{count}})</div></div>';

			$tray = '<img class="sprite" style="height: 3em; width: auto;" src="{{url}}" alt="{{name}}">';
		
			if($p['type'] == 'item')
			{
				if(isset($p['count'])?$p['count'] == '':true)
				{
					$p['count'] = 1;
				}
			
				$tray =
				str_replace
				(
					'{{url}}',
					'/Sprites/item/'.$p['name'].'.png',
					$tray
				);

				$tray = 
				str_replace
				(
					'{{name}}',
					$p['name'],
					$tray
				);
				
				$trayx=$tray;
				
				$template = str_replace('{{mood}}',($p['mood']!==''?$p['mood'].'&nbsp;':''),$template);

				$template = str_replace('{{count}}',$p['count'].'x',$template);

				for
				(
					$trayindex = 2;
					$trayindex<=$p['count'];
					$trayindex++
				)
				{
					$trayx .= $tray;
				}
				
				$template = str_replace
				(
					'{{tray}}',
					$trayx,
					$template
				);
				
				$result .=$template;
			}
		}
		return $result;
	}
	public function disp_option($page = []): string
	{
		$result = '';
				
		for($index = 1;$index <=$page['option']['count']; $index++)
		{
			$p = $page['option'][$index];

			$template = '<button type="submit" name="q" value="{{key}}">{{caption}}<br>(Hint:&nbsp;{{condition}}&nbsp;{{hint}})</button>';

			$template = str_replace('{{key}}',$p['key'],$template);

			$template = str_replace('{{caption}}',$p['caption'],$template);

			$template = str_replace('{{condition}}',$p['condition'],$template);
			
			if(!isset($this->history[$page['id']['title']]))
			{
				$this->history[$page['id']['title']] = 0;
			}
			$template = str_replace('{{hint}}',($this->history[$page['id']['title']] == 0?'Frontier':'Familiar'),$template);
			
			$result .=$template;
		}
		return $result;
	}
	public function disp_caption($page = []): string
	{
		$result = '';
		
		for($index = 2;$index <=$page['caption']['count']; $index++)
		{
			$p = $page['caption'][$index];
			if( ($p['caption'] !== '') && ($result !== '') )
			{
				$result .= ' ';
			}
			$template = '{{caption}}';
			
			$template = str_replace( '{{caption}}', $p['caption'], $template );
			
			$result .= $template;
		
		}
		return $result;
	}

	public function disp_page ($page = []): string
	{
		$result = '';
		
		foreach( [ 'header', 'encounter',  'bric_a_brac', 'option', 'caption' ] as $key )
		{
			$temp = $this->{"disp_$key"}($page);
			if($temp !== '')
			{
				$result .= '<div>'.$temp.'</div>';
			}				
		}

		return $result;
	}
	public function history_load(string $id = 'test'): array
	{
		if (is_file ( $this->fixpath ( __DIR__.'/../d22-prviate/history/'.$id.'.json' ) ))
		{
			return json_decode(file_get_contents( $this->fixpath (__DIR__.'/../d22-prviate/history/'.$id.'.json')),true);
		}
	}
	public function history_save(string $id = 'test', array $history = [])
	{
		file_put_contents($this->fixpath (__DIR__.'/../d22-prviate/history/'.$id.'.json'),json_encode($history, JSON_PRETTY_PRINT));
	}
	/*
	public function build_scene(): string
	{		
		$template = 
		'<div class="image-container">
		<img src="{{illustration}}" alt="{{alt}}">
		<div class="text-overlay">{{text}}</div>
		</div>';
		
		$p = $page[]
		
		$template = str_replace( '{{illustration}}', $p['header'][1]['illustration'].'.png', $template);

		$template = str_replace( '{{text}}', $this->pagebuffer, $template);
		
		$this->pagebuffer = $template;
		
		return this->pagebuffer;
	}
	*/
}