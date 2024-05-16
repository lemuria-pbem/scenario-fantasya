# Handlungen

Nichtspielercharaktere können neben einfachen Befehlen auch komplexe Handlungen
ausführen, deren Abläufe einer individuellen Logik folgen und ihrerseits neue
Befehle erzeugen können.

Eine Handlung kann ein Ergebnis erzeugen, das bestimmt, ob weitere nachfolgende
Handlungen ausgeführt werden.

## Ankauf

Syntax: `Ankauf([Gegenstand], …)`

Die Einheit möchte besondere Gegenstände erwerben und macht Besuchern
entsprechende Angebote. Es können einzelne Gegenstandsarten angegeben werden,
wenn nicht für alle Gegenstände Angebote gemacht werden sollen.

## Folgen

Syntax: `Folgen([ID, [Dauer]])`

Die Einheit folgt der Spielereinheit für eine bestimmte Anzahl von Runden oder
bis auf Widerruf. Wenn keine Parameter angegeben sind, folgt die Einheit ab
sofort nicht mehr.

## Gerüchte

Syntax: `Gerüchte([Begegnung|Kampf|Markt|Monster|Steuer], …)`

Sammelt alle Vorkommnisse der angegebenen Art in den durchreisten Regionen. Ohne
Angabe der Art werden alle Arten von Gerüchten gesammelt.

## Händler

Syntax: `Händler()`

Wenn sich die Einheit nicht in einem Gebäude aufhält, bietet sie ihre Waren
allen Parteien an, deren Einheiten sich in der Region aufhalten.

## Lehrer

Syntax: `Lehrer([Talent], …)`

Die Einheit bietet ihre Dienste als Lehrer für die angegebenen Talente an. Ohne
Angabe eines Talents werden alle erlernten Talente angeboten.

## Marktstand

Syntax: `Marktstand(n)`

1. Wenn in der Region ein Markt existiert, betritt die Einheit den Markt.
2. Solange andere Einheiten Handel treiben, bleibt die Einheit im Markt, und es
   wird keine weitere Handlung ausgeführt.
3. Nach _n_ Runden ohne Handel verlässt die Einheit den Markt und führt die
   nächste Handlung aus.

### LetzterHandel

Der Schlüssel _LetzterHandel_ speichert, wie viele Runden seit dem letzten
Handel vergangen sind.

## Reise

Syntax: `Reise(ID)`

Reist auf dem kürzesten Weg zu der Region mit der Nummer _ID_.

## Rundreise

Syntax: `Rundreise(a, b, …)`

Eine Rundreise funktioniert wie der Befehl `ROUTE`. Die Einheit sucht sich die
günstigste Reiseroute zur nächsten Region und reist dorthin, indem ein passender
Reisebefehl erstellt wird.

Die Ziele werden jeweils nach Erreichen der nächsten Region rotiert.

## Schiffspassage

Syntax: `Schiffspassage(a, b, [Bezahlung])`

Wenn sich die Einheit in der Region a befindet, unterbricht sie ihre Weiterreise
bietet einen Transportauftrag an, um sich selbst per Schiff in die Region b
bringen zu lassen. Wenn sie in der Region b ankommt, ist der Auftrag erfüllt und
die Reise wird fortgesetzt.

Als Bezahlung können ein oder mehrere Gegenstandstupel angegeben werden. Ohne
Angabe wird eine Silbermenge abhängig vom Gewicht der Einheit berechnet.
