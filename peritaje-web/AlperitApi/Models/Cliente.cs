using System;
using System.Collections.Generic;

namespace AlperitApi.Models;

public partial class Cliente
{
    public int Id { get; set; }

    public string Nombre { get; set; } = null!;

    public string Dni { get; set; } = null!;

    public string Email { get; set; } = null!;

    public string? Telefono { get; set; }

    public DateTime FechaAlta { get; set; }

    public virtual ICollection<Solicitude> Solicitudes { get; set; } = new List<Solicitude>();
}
