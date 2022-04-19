<?php

namespace J0sh0nat0r\Oso;

enum PolarOperator: string
{
    case Add = 'Add';
    case And = 'And';
    case Assign = 'Assign';
    case Cut = 'Cut';
    case Debug = 'Debug';
    case Div = 'Div';
    case Dot = 'Dot';
    case ForAll = 'ForAll';
    case In = 'In';
    case Isa = 'Isa';
    case Mod = 'Mod';
    case Mul = 'Mul';
    case New = 'New';
    case Not = 'Not';
    case Or = 'Or';
    case Print = 'Print';
    case Rem = 'Rem';
    case Sub = 'Sub';
    case Unify = 'Unify';
    // Comparison operators
    case Eq = 'Eq';
    case Geq = 'Geq';
    case Gt = 'Gt';
    case Leq = 'Leq';
    case Lt = 'Lt';
    case Neq = 'Neq';
}
