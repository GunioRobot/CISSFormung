# CodeIgniter Scriptshit Formung (cissFormung)

"cissFormung" versucht das erstellen von Formularen, dessen Absenden, Validierung und Weiterverarbeitung in einer Library zusammenzufassen. 

Innerhalb eines Controllers reicht es ein multidimensionales Array zu erstellen und es an die "cissFormung" Klasse zu übergeben. Die Klasse bildet daraus ein XHTML valides, per CSS stylebares Formular. Sendet man das Form ab, übernimmt die Klasse Validierung und Verarbeitung. 

Bei einem Fehler wird man auf das Formular zurückgeworfen, fehlende oder falsche Felder werden angemerkt. Bei erfolgreicher Validierung bekommt man die Daten des Formulars zur Weiterverarbeitung in form eines Arrays.