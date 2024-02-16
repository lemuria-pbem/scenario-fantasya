# Skripte

Nichtspielercharaktere werden über Skripte gesteuert. Skripte können neue
Einheiten und andere Entitäten erzeugen, oder einfache Befehle und komplexe
Handlungen für die Einheiten setzen.

Skripte werden im INI-Format abgelegt; jede Sektion ist ein Skript.

## Allgemeine Parameter

### ID

Die Nummer einer Entität wird einheitlich mit dem Schlüssel _ID_ angegeben:

    ID = n1

#### Einheiten-ID

Die Nummer einer Einheit kann auch hinter dem Skriptnamen in der Sektion
angegeben werden:

    [Skript n1]

### Runde

Jedes Skript kann den Schlüssel _Runde_ enthalten, der angibt, in welcher Runde
das Skript ausgeführt wird.

    Runde = 123

### Name

    Name = Galbrak

### Beschreibung

    Beschreibung = Hier steht ein Text.

## Erzeugung neuer Entitäten

### Einheit

Das Skript **[Einheit]** erzeugt eine neue Einheit. Die _ID_ ist optional und
wird nur benötigt, wenn andere Skripte sich auf die neue Einheit beziehen.

Notwendige Schlüssel:

    Rasse = Zwerg
    Region|Gebäude|Schiff = n1

Optionale Schlüssel:

    Größe = 2
    Besitz = 1 Wagen, 2 Pferde, 2000 Silber
    Besitz = 20 Juwelen, 40 Balsam, 40 Pelze
    Talent = Handeln 5, Ausdauer 1
    Talent = Reiten 2

### Gebäude

Gebäude können auf drei verschiedene Arten erzeugt werden:

    [Gebäude]
    [Burg]

Wird **[Gebäude]** verwendet, muss der Gebäudetyp-Schlüssel angegeben werden.
Bei **[Burg]** genügt es, wenn die Größe angegeben wird - dann erhält die Burg
den zur Größe passenden Gebäudetyp. Der Gebäudetyp kann auch direkt als
Sektionsname angegeben werden:

    [Sägewerk]

Notwendige Schlüssel:

    Region = n1

Optionale Schlüssel:

    Gebäude = Sägewerk    
    Größe = 5

### Schiff

Schiffe können auf zwei verschiedene Arten erzeugt werden:

    [Schiff]

Hier muss der Schiffstyp als Schlüssel angegeben werden.

Der Schiffstyp kann auch direkt als Sektionsname verwendet werden:

    [Langboot]

Notwendige Schlüssel:

    Region = n1

Optionale Schlüssel:

    Schiff = Langboot

### Unikat

Unikate können auf zwei verschiedene Arten erzeugt werden:

    [Gegenstand]

Hier muss der Gegenstandstyp als Schlüssel angegeben werden.

Der Gegenstandstyp kann auch direkt als Sektionsname verwendet werden:

    [Ring der Unsichtbarkeit]

Notwendige Schlüssel:

    Einheit|Region|Gebäude|Schiff = n1

Optionale Schlüssel:

    Gegenstand = Ring der Unsichtbarkeit

## Gerücht

Ein oder mehrere gesammelte Gerüchte werden in dieser Sektion angegeben.

    [Gerücht]
    Jedes Gerücht ist eine Zeile.

Optionale Schlüssel:

    Runden = 3

Wenn eine Rundenanzahl angegeben ist, wird diese heruntergezählt und das Gerücht
beim Ablauf des Countdowns gelöscht.

## Notizen

In einer Notizen-Sektion kann beliebiger mehrzeiliger Text geschrieben werden,
um die Skriptdatei zu kommentieren. Die Sektion wird unverändert in die neue
Skriptdatei übernommen.

    [Notizen]
    Hier steht der Kommentar.

## Skript

Eine Sektion **[Skript]** legt einfache Befehle oder komplexe Handlungen für
eine Einheit fest.

Befehle und Handlungen können gemischt werden.

### Befehle

    [Skript h]
    VORGABE Wiederholen
    ANGEBOT * Juwelen 1 Myrrhe

### Handlungen

Handlungen werden als _Makro_ mit Parametern in Klammern angegeben; dadurch
werden sie von Befehlen unterschieden.

    [Skript]
    ID = h
    Marktstand(3)
    Rundreise(r1, r2, r3)
