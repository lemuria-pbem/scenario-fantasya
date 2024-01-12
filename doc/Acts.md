# Handlungen

Nichtspielercharaktere können neben einfachen Befehlen auch komplexe Handlungen
ausführen, deren Abläufe einer individuellen Logik folgen und ihrerseits neue
Befehle erzeugen können.

Eine Handlung kann ein Ergebnis erzeugen, das bestimmt, ob weitere nachfolgende
Handlungen ausgeführt werden.

## Gerüchte([Begegnung|Kampf|Markt|Monster|Steuer], …)

Sammelt alle Vorkommnisse der angegebenen Art in den durchreisten Regionen. Ohne
Angabe der Art werden alle Arten von Gerüchten gesammelt.

## Marktstand(_n_)

1. Wenn in der Region ein Markt existiert, betritt die Einheit den Markt.
2. Solange andere Einheiten Handel treiben, bleibt die Einheit im Markt, und es
   wird keine weitere Handlung ausgeführt.
3. Nach _n_ Runden ohne Handel verlässt die Einheit den Markt und führt die
   nächste Handlung aus.

### LetzterHandel

Der Schlüssel _LetzterHandel_ speichert, wie viele Runden seit dem letzten
Handel vergangen sind.

## Reise(_ID_)

Reist auf dem kürzesten Weg zu einer Region.

## Rundreise(_a_, _b_, …)

Eine Rundreise funktioniert wie der Befehl `ROUTE`. Die Einheit sucht sich die
günstigste Reiseroute zur nächsten Region und reist dorthin, indem ein passender
Reisebefehl erstellt wird.
