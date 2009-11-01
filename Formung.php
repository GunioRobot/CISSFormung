<?php
/**
 * Baut aus einem Array ein Formular mit Überprüfung der Inhalte und Rückgabe
 * der Werte. Man kann bequem in einem Controller das Formular ausgeben,
 * die eingegebenen Werte prüfen und speichern.
 *
 * @package scriptshit.de
 * @author Robert Agthe
 * @version 8.Oktober.2009
 */

class Formung {

	var $CI;
	var $data = array();
	var $output;
	var $oldfiles = array(); // Gelöschte Dateien
	var $button = 'Absenden';
	var $dbformdata;
	var $upload_error;
	var $return = FALSE;
	
	function Formung()
	{
		$this->CI =& get_instance();
		$this->CI->load->library('form_validation');
		$this->CI->load->library('upload');
	}
	
	function fillForm()
	{
		$this->CI->db->from($this->data['table']);
		$this->CI->db->where('id',$this->data['id']);
		$this->dbformdata = $this->CI->db->get()->row(1);
		foreach($this->data['fields'] as $key => $value)
		{
			$this->data['fields'][$key]['value'] = $this->dbformdata->$key;
		}
	}
	
	function showForm()
	{
		$this->output = form_open_multipart($this->data['segment'].$this->data['id']);
		if(isset($this->data['id']) AND $this->data['id'] != 0)
		{
			$this->output.= form_hidden('id',$this->data['id']);
		}
		foreach($this->data['fields'] as $key => $value)
		{
			$this->output.= "\n".'<div id="ssForm_'.$key.'">';
			
			switch($value['type'])
			{
				case 'hidden':
					$this->output.= "\n	".form_hidden($key, $value['value']);
					break;

				case 'text':
					$this->output.= "\n	".form_label($value['desc'], $key);
					$this->output.= "\n	".form_input($key, $value['value']);
					$this->output.= "\n".form_error($key);
					break;

				case 'date':
					$this->output.= "\n	".form_label($value['desc'], $key);
					$this->output.= "\n	".form_input($key, $value['value'],'class="ssDate"');
					$this->output.= "\n".form_error($key);
					break;

				case 'textarea':
					$this->output.= "\n	".form_label($value['desc'], $key);
					$this->output.= "\n	".form_textarea($key, $value['value']);
					$this->output.= "\n".form_error($key);
					break;

				case 'dropdown':
					$this->output.= "\n	".form_label($value['desc'], $key);
					$this->output.= "\n	".form_dropdown($key, $value['options'], $value['value']);
					$this->output.= "\n".form_error($key);
					break;

				case 'checkbox':
					$this->output.= "\n	".form_label($value['desc'], $key);
					$this->output.= "\n	".form_checkbox($key, $value['value'], $value['checked']);
					$this->output.= "\n".form_error($key);
					break;

				case 'upload':
					$this->output.= "\n	".form_label($value['desc'], $key);
					$this->output.= "\n	".form_upload($key, $value['value']);
					$this->output.= "\n".(is_array($this->upload_error))?'<p>Err:'.$this->upload_error[$key].'</p>':'<p></p>';
					break;

				default:
					break;
			}
			$this->output.= '</div>';
		}
		$this->output.= "\n".'<div>'."\n	".form_submit('submit',$this->button,'class="submit"')."\n".'</div>';
		$this->output.= "\n".form_close();
		return $this->output;
		
	}

	function checkPost()
	{
		$this->CI->load->library('form_validation');
		foreach($this->data['fields'] as $key => $value)
		{
			// Wenn Regeln gesetzt wurden, dann auch anwenden
			if(isset($value['rules']))
			{
				$this->CI->form_validation->set_rules(
					$key, 
					$value['desc'], 
					$value['rules']
				);
				$check = $this->CI->form_validation->run();
			}
			else
			{
				$check = TRUE;
			}
		}
		
		// Prüfen ob Regeln angwendet und auch eingehalten werden
		if($check == FALSE)
		{
			// Da fehlt ein Feld. Form ausgeben und Fehler ausgeben
			foreach($this->data['fields'] as $key => $value)
			{
				$this->data['fields'][$key]['value'] = $this->CI->input->post($key);
			}
			$this->showForm();
			$this->return = FALSE;
		}
		else
		{
			// Wenn alle Regeln befolgt wurden, Form annehmen und verarbeiten
			// Alle Felder im Array durchgehen
			foreach($this->data['fields'] as $key => $value)
			{

				// Wenn Feld Typ "Upload", dann Upload starten
				if($value['type'] == 'upload')
				{
					// Upload durchführen und prüfen ob er geklappt hat
					if($this->upload($key))
					{
						$data = $this->CI->upload->data();
						$this->return[$key] = $data['file_name'];
						unset($data);
					}
					else
					{
						//echo "eeeyyyyyyyyyyye";
						// Wenn der Upload schief ging, Form mit 
						// Fehlermeldungausgeben
						//unset($this->return[$key]);
						//$this->showForm();
						//$this->return = FALSE;
					}
				}
				else 
				{
					if($value['type'] != 'upload')
					{
						// Wenn Feld kein Upload ist, dann im Return Array
						// speichern und weitermachen.
						$this->return[$key] = $this->CI->input->post($key);
					}
				}
			}

			// Wenn der Feldname "id" ist, dann als ID für Datenbank 
			// verwenden
			if($this->CI->input->post('id') != '0')
			{
				$this->return['id'] = $this->CI->input->post('id');
			}
			
			// Wenn eine Permission angegeben wurde
			if(isset($this->data['permission']))
			{
				$this->return['_permission'] = $this->data['permission'];
			}
		}
	}
	
	function upload($field)
	{
		
		$this->CI->upload->initialize($this->data['fields'][$field]['config']);
		if (!$this->CI->upload->do_upload($field))
		{
			$this->upload_error[$field] = $this->CI->upload->display_errors();
			return FALSE;
		}
		else
		{
			// Upload hat ein neues Bild hochgeladen
			// Wenn altes Bild vorhanden, dann löschen
			$this->CI->db->from($this->data['table']);
			$this->CI->db->where('id',$this->CI->input->post('id'));
			if(is_object($row = $this->CI->db->get()->row(1)))
			{
				$this->dbformdata = $row->$field;
				$datei = $this->data['fields'][$field]['config']['upload_path'].$this->dbformdata;
				if($this->dbformdata and file_exists($datei))
				{
					@unlink($datei);
					
					// Von der Datei die eben gelöscht wurde den Namen 
					// merken. Evtl gibt es noch irgendwo Thumbs die
					// gelöscht werden müssen.
					$this->oldfiles[] = $this->dbformdata;
				}
				unset($datei);	
			}
			return TRUE;
		}
	}

	function success($data='')
	{
		if(is_array($data))
		{
			$this->data = $data;
			if(isset($this->data['button']))
			{
				$this->button = $this->data['button'];
			}
		}

		if($_POST)
		{
			$this->checkPost();
			return $this->return;
		}
		else
		{
			if(isset($this->data['id']) AND $this->data['id'] != 0)
			{
				$this->fillForm();
			}
			$this->showForm();
		}
	}
}