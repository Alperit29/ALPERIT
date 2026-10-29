using System;
using System.Collections.Generic;

namespace AlperitApi.Models;

public partial class Usuario
{
    public int Id { get; set; }

    public string? Nombre { get; set; }

    public string? Email { get; set; }

    public string? Password { get; set; }

    public string? Rol { get; set; }

    public DateTime? FechaCreacion { get; set; }

    public virtual ICollection<Peritaje> Peritajes { get; set; } = new List<Peritaje>();
}
