using System;
using System.Collections.Generic;

namespace AlperitApi.Models;

public partial class Peritaje
{
    public int Id { get; set; }

    public int? VehiculoId { get; set; }

    public DateOnly? FechaPeritaje { get; set; }

    public string? EstadoGeneral { get; set; }

    public string? Observaciones { get; set; }

    public decimal? PrecioEstimado { get; set; }

    public int? UsuarioId { get; set; }

    public virtual ICollection<FotosPeritaje> FotosPeritajes { get; set; } = new List<FotosPeritaje>();

    public virtual Usuario? Usuario { get; set; }

    public virtual Vehiculo? Vehiculo { get; set; }
}
