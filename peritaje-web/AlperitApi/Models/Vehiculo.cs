using System;
using System.Collections.Generic;

namespace AlperitApi.Models;

public partial class Vehiculo
{
    public int Id { get; set; }

    public string? Patente { get; set; }

    public string? Marca { get; set; }

    public string? Modelo { get; set; }

    public int? Anio { get; set; }

    public int? Kilometros { get; set; }

    public string? Transmision { get; set; }

    public string? Moneda { get; set; }

    public decimal? Precio { get; set; }

    public virtual ICollection<Peritaje> Peritajes { get; set; } = new List<Peritaje>();

    public virtual ICollection<Solicitude> Solicitudes { get; set; } = new List<Solicitude>();
}
