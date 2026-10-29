using System;
using System.Collections.Generic;

namespace AlperitApi.Models;

public partial class Solicitude
{
    public int Id { get; set; }

    public string Nombre { get; set; } = null!;

    public string? Dni { get; set; }

    public string Email { get; set; } = null!;

    public string? Telefono { get; set; }

    public string Marca { get; set; } = null!;

    public string? Modelo { get; set; }

    public int? AnioDesde { get; set; }

    public int? AnioHasta { get; set; }

    public int? AnioMinimo { get; set; }

    public string? Transmision { get; set; }

    public int? KilometrajeMaximo { get; set; }

    public string? Moneda { get; set; }

    public decimal? PresupuestoMaximo { get; set; }

    public string? FormaCompra { get; set; }

    public string? Observaciones { get; set; }

    public int? VehiculoSeleccionadoId { get; set; }

    public string Estado { get; set; } = null!;

    public DateTime FechaCreacion { get; set; }

    public int? ClienteId { get; set; }

    public virtual Cliente? Cliente { get; set; }

    public virtual Vehiculo? VehiculoSeleccionado { get; set; }
}
