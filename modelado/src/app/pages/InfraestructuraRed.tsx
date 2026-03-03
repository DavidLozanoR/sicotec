import { useData } from "../context/DataContext";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "../components/ui/card";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "../components/ui/table";
import { Badge } from "../components/ui/badge";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "../components/ui/tabs";
import { Network, Wifi, CheckCircle2, Circle } from "lucide-react";

export function InfraestructuraRed() {
  const { segmentosRed, personalOperativo } = useData();

  const getIPsOcupadas = (segmento: typeof segmentosRed[0]) => {
    const ipsOcupadas: { ip: string; mac: string; responsable: string }[] = [];
    
    personalOperativo.forEach((personal) => {
      if (personal.segmento === segmento.nombre) {
        personal.equiposAsignados?.forEach((asignacion) => {
          if (asignacion.ip) {
            ipsOcupadas.push({
              ip: asignacion.ip,
              mac: "-", // La MAC está en el equipo global, no en la asignación
              responsable: personal.nombre,
            });
          }
        });
      }
    });
    
    return ipsOcupadas;
  };

  const getTotalIPs = (rangoInicio: string, rangoFin: string) => {
    const inicio = rangoInicio.split(".").pop() || "0";
    const fin = rangoFin.split(".").pop() || "0";
    return parseInt(fin) - parseInt(inicio) + 1;
  };

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold">Infraestructura de Red</h1>
        <p className="text-muted-foreground mt-1">
          Visualización de segmentos, rangos de IP y asignaciones
        </p>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        <Card>
          <CardHeader className="pb-3">
            <CardTitle className="text-sm font-medium text-muted-foreground">
              Total Segmentos
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="flex items-center gap-2">
              <Network className="w-5 h-5 text-primary" />
              <span className="text-2xl font-bold">{segmentosRed.length}</span>
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="pb-3">
            <CardTitle className="text-sm font-medium text-muted-foreground">
              IPs Asignadas
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="flex items-center gap-2">
              <CheckCircle2 className="w-5 h-5 text-accent" />
              <span className="text-2xl font-bold">
                {segmentosRed.reduce((acc, s) => acc + getIPsOcupadas(s).length, 0)}
              </span>
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="pb-3">
            <CardTitle className="text-sm font-medium text-muted-foreground">
              IPs Disponibles
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="flex items-center gap-2">
              <Circle className="w-5 h-5 text-muted-foreground" />
              <span className="text-2xl font-bold">
                {segmentosRed.reduce(
                  (acc, s) =>
                    acc +
                    (getTotalIPs(s.rangoInicio, s.rangoFin) - getIPsOcupadas(s).length),
                  0
                )}
              </span>
            </div>
          </CardContent>
        </Card>
      </div>

      <Tabs defaultValue={segmentosRed[0]?.id || "0"} className="space-y-4">
        <TabsList className="w-full justify-start overflow-x-auto">
          {segmentosRed.map((segmento) => (
            <TabsTrigger key={segmento.id} value={segmento.id}>
              <Wifi className="w-4 h-4 mr-2" />
              {segmento.nombre}
            </TabsTrigger>
          ))}
        </TabsList>

        {segmentosRed.map((segmento) => {
          const ipsOcupadas = getIPsOcupadas(segmento);
          const totalIPs = getTotalIPs(segmento.rangoInicio, segmento.rangoFin);
          const ipsDisponibles = totalIPs - ipsOcupadas.length;

          return (
            <TabsContent key={segmento.id} value={segmento.id} className="space-y-4">
              <Card>
                <CardHeader>
                  <CardTitle>{segmento.nombre}</CardTitle>
                  <CardDescription>{segmento.piso}</CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                  <div className="grid grid-cols-1 md:grid-cols-3 gap-4 p-4 bg-muted/50 rounded-lg">
                    <div>
                      <p className="text-sm font-medium text-muted-foreground mb-1">
                        Rango de IPs
                      </p>
                      <p className="text-sm font-mono">
                        {segmento.rangoInicio} - {segmento.rangoFin}
                      </p>
                    </div>
                    <div>
                      <p className="text-sm font-medium text-muted-foreground mb-1">
                        IPs Asignadas
                      </p>
                      <div className="flex items-center gap-2">
                        <Badge variant="default" className="bg-accent text-accent-foreground">
                          {ipsOcupadas.length} / {totalIPs}
                        </Badge>
                        <span className="text-xs text-muted-foreground">
                          ({Math.round((ipsOcupadas.length / totalIPs) * 100)}% ocupado)
                        </span>
                      </div>
                    </div>
                    <div>
                      <p className="text-sm font-medium text-muted-foreground mb-1">
                        IPs Disponibles
                      </p>
                      <Badge variant="secondary">{ipsDisponibles}</Badge>
                    </div>
                  </div>

                  {ipsOcupadas.length > 0 ? (
                    <div>
                      <h3 className="font-semibold mb-3">IPs Asignadas y Operando</h3>
                      <div className="border rounded-lg">
                        <Table>
                          <TableHeader>
                            <TableRow>
                              <TableHead>Dirección IP</TableHead>
                              <TableHead>Dirección MAC</TableHead>
                              <TableHead>Responsable</TableHead>
                              <TableHead>Estado</TableHead>
                            </TableRow>
                          </TableHeader>
                          <TableBody>
                            {ipsOcupadas.map((item, idx) => (
                              <TableRow key={idx}>
                                <TableCell className="font-mono">{item.ip}</TableCell>
                                <TableCell className="font-mono text-sm">{item.mac}</TableCell>
                                <TableCell>{item.responsable}</TableCell>
                                <TableCell>
                                  <Badge
                                    variant="default"
                                    className="bg-accent text-accent-foreground"
                                  >
                                    <CheckCircle2 className="w-3 h-3 mr-1" />
                                    Operando
                                  </Badge>
                                </TableCell>
                              </TableRow>
                            ))}
                          </TableBody>
                        </Table>
                      </div>
                    </div>
                  ) : (
                    <div className="text-center py-8 text-muted-foreground">
                      <Circle className="w-12 h-12 mx-auto mb-3 opacity-50" />
                      <p>No hay IPs asignadas en este segmento</p>
                    </div>
                  )}
                </CardContent>
              </Card>
            </TabsContent>
          );
        })}
      </Tabs>
    </div>
  );
}