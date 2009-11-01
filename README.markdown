# CodeIgniter Scriptshit Formung (cissFormung)

"cissFormung" versucht das erstellen von Formularen, dessen Absenden, Validierung und Weiterverarbeitung in einer Library zusammenzufassen. 

Innerhalb eines Controllers reicht es ein multidimensionales Array zu erstellen und es an die "cissFormung" Klasse zu übergeben. Die Klasse bildet daraus ein XHTML valides, per CSS stylebares Formular. Sendet man das Form ab, übernimmt die Klasse Validierung und Verarbeitung. 

Bei einem Fehler wird man auf das Formular zurückgeworfen, fehlende oder falsche Felder werden angemerkt. Bei erfolgreicher Validierung bekommt man die Daten des Formulars zur Weiterverarbeitung in form eines Arrays.

# Beispiel
Sagen wir, wir haben eine MySQL Tabelle (books) in der wir Bücher eintragen möchten. Das ganze soll im Backend passieren und per Formular abgewickelt werden. Dazu brauchen wir einen Controller und die "cissFormung" Library.

	class Books extends Controller
	{
		function Books()
		{
			parent::Controller();
		}

		function modify()
		{
			$data = array(
				'table' =>'books', // MySQL Tabelle
				'id' => $this->uri->segment(4), // id für den Datensatz
				'segment'=>'admin/books/modify', // URL zu diesem Controller
				'fields' => array( // Input Felder
					'name'=>array( // Name muss MySQL Coloumn Name entsprechen
						'desc'=>'Buchname', // Labeltext
						'value'=>'', // Schon vorhandener Text
						'type'=>'text', // text, dropdown, upload etc.
						'rules'=>'required' // CI Form Validation Rules
					),
					'preis'=>array(
						'desc'=>'Wieviel kostet das Buch?',
						'value'=>'',
						'type'=>'text',
						'rules'=>'required'
					),
					'art'=>array(
						'desc'=>'Buchart?',
						'value'=>'',
						'type'=>'dropdown',
						'rules'=>'required',
						'options'=>array( // Wenn dropdown -> Options Array
							'0'=>'Dickes Buch',
							'1'=>'dünnes Buch'
						)
					),
				),
			);

			// cissFormung laden
			$this->load->library('Formung');

			// Daten Array an Klasse übergeben
			$this->formung->data = $data;

			// Prüfen ob Form Fehler verursacht hat
			if($this->formung->success())
			{
				// Keine Fehler. Daten weiterverarbeiten
				$this->book_model->safe($this->formung->return);
				redirect('backend/admin_steine');
			}
			else
			{
				// Fehler oder Aufruf ohne POST
				echo $this->formung->output;
			}

		}
	}